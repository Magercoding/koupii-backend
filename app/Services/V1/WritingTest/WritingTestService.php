<?php

namespace App\Services\V1\WritingTest;

use App\Models\WritingTask;
use App\Models\WritingTaskAssignment;
use App\Models\WritingSubmission;
use App\Models\WritingReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WritingTestService
{
    /**
     * Create a new writing task.
     */
    public function create(array $data, Request $request): WritingTask
    {
        return DB::transaction(function () use ($data, $request) {
            $task = WritingTask::create([
                'id' => Str::uuid(),
                'creator_id' => Auth::id(),
                'title' => $data['title'],
                'description' => $data['description'],
                'instructions' => $data['instructions'] ?? null,
                'sample_answer' => $data['sample_answer'] ?? null,
                'word_limit' => $data['word_limit'] ?? null,
                'allow_retake' => $data['allow_retake'] ?? false,
                'max_retake_attempts' => $data['max_retake_attempts'] ?? null,
                'retake_options' => $data['retake_options'] ?? null,
                'timer_type' => $data['timer_type'] ?? 'none',
                'time_limit_seconds' => $data['time_limit_seconds'] ?? null,
                'allow_submission_files' => $data['allow_submission_files'] ?? false,
                'is_published' => $data['is_published'] ?? false,
                'due_date' => $data['due_date'] ?? null,
            ]);

            return $task->load('creator');
        });
    }

    /**
     * Update an existing writing task.
     */
    public function updateTask(WritingTask $task, array $data, Request $request): WritingTask
    {
        return DB::transaction(function () use ($task, $data, $request) {
            $task->update(array_filter([
                'title' => $data['title'] ?? $task->title,
                'description' => $data['description'] ?? $task->description,
                'instructions' => $data['instructions'] ?? $task->instructions,
                'sample_answer' => $data['sample_answer'] ?? $task->sample_answer,
                'word_limit' => $data['word_limit'] ?? $task->word_limit,
                'allow_retake' => $data['allow_retake'] ?? $task->allow_retake,
                'max_retake_attempts' => $data['max_retake_attempts'] ?? $task->max_retake_attempts,
                'retake_options' => $data['retake_options'] ?? $task->retake_options,
                'timer_type' => $data['timer_type'] ?? $task->timer_type,
                'time_limit_seconds' => $data['time_limit_seconds'] ?? $task->time_limit_seconds,
                'allow_submission_files' => $data['allow_submission_files'] ?? $task->allow_submission_files,
                'is_published' => $data['is_published'] ?? $task->is_published,
                'due_date' => $data['due_date'] ?? $task->due_date,
            ]));

            return $task->load('creator');
        });
    }

    /**
     * Send task to classrooms (assign to classes).
     */
    public function assignToClassrooms(WritingTask $task, array $classroomIds): array
    {
        $assignments = [];

        DB::transaction(function () use ($task, $classroomIds, &$assignments) {
            foreach ($classroomIds as $classroomId) {
                $assignment = WritingTaskAssignment::create([
                    'id' => Str::uuid(),
                    'writing_task_id' => $task->id,
                    'classroom_id' => $classroomId,
                    'assigned_by' => Auth::id(),
                    'assigned_at' => now(),
                ]);

                $assignments[] = $assignment;
            }

            // Publish the task when assigned
            $task->update(['is_published' => true]);
        });

        return $assignments;
    }

    /**
     * Submit student writing.
     */
    public function submitWriting(WritingTask $task, array $data): WritingSubmission
    {
        return DB::transaction(function () use ($task, $data) {
            // Check if student already has a submission for this task
            $existingSubmission = WritingSubmission::where('writing_task_id', $task->id)
                ->where('student_id', Auth::id())
                ->where('status', '!=', 'submitted')
                ->first();

            if ($existingSubmission) {
                // Update existing draft
                $existingSubmission->update([
                    'content' => $data['content'],
                    'files' => $data['files'] ?? null,
                    'word_count' => $this->countWords($data['content']),
                    'status' => 'submitted',
                    'time_taken_seconds' => $data['time_taken_seconds'] ?? null,
                    'submitted_at' => now(),
                ]);

                return $existingSubmission;
            } else {
                // Create new submission
                $attemptNumber = WritingSubmission::where('writing_task_id', $task->id)
                    ->where('student_id', Auth::id())
                    ->max('attempt_number') + 1;

                return WritingSubmission::create([
                    'id' => Str::uuid(),
                    'writing_task_id' => $task->id,
                    'student_id' => Auth::id(),
                    'content' => $data['content'],
                    'files' => $data['files'] ?? null,
                    'word_count' => $this->countWords($data['content']),
                    'status' => 'submitted',
                    'attempt_number' => $attemptNumber,
                    'time_taken_seconds' => $data['time_taken_seconds'] ?? null,
                    'submitted_at' => now(),
                ]);
            }
        });
    }

    /**
     * Save draft (auto-save functionality).
     */
    public function saveDraft(WritingTask $task, array $data): WritingSubmission
    {
        $submission = WritingSubmission::updateOrCreate(
            [
                'writing_task_id' => $task->id,
                'student_id' => Auth::id(),
                'status' => 'to_do'
            ],
            [
                'id' => Str::uuid(),
                'content' => $data['content'],
                'files' => $data['files'] ?? null,
                'word_count' => $this->countWords($data['content']),
                'time_taken_seconds' => $data['time_taken_seconds'] ?? null,
            ]
        );

        return $submission;
    }

    /**
     * Create retake submission based on retake option.
     */
    public function createRetakeSubmission(WritingTask $task, string $retakeOption, array $data = []): WritingSubmission
    {
        return DB::transaction(function () use ($task, $retakeOption, $data) {
            $attemptNumber = WritingSubmission::where('writing_task_id', $task->id)
                ->where('student_id', Auth::id())
                ->max('attempt_number') + 1;

            // Check if retakes are allowed
            if (
                !$task->allow_retake ||
                ($task->max_retake_attempts && $attemptNumber > $task->max_retake_attempts)
            ) {
                throw new \Exception('Retakes not allowed or maximum attempts exceeded');
            }

            $previousSubmission = WritingSubmission::where('writing_task_id', $task->id)
                ->where('student_id', Auth::id())
                ->orderBy('attempt_number', 'desc')
                ->first();

            $content = '';

            switch ($retakeOption) {
                case 'rewrite_all':
                    $content = ''; // Start fresh
                    break;

                case 'group_similar':
                    $content = $this->generateSimilarMistakesTemplate($previousSubmission);
                    break;

                case 'choose_any':
                    $content = $this->generateChosenMistakesTemplate($previousSubmission, $data['chosen_mistakes'] ?? []);
                    break;

                default:
                    throw new \Exception('Invalid retake option');
            }

            return WritingSubmission::create([
                'id' => Str::uuid(),
                'writing_task_id' => $task->id,
                'student_id' => Auth::id(),
                'content' => $content,
                'status' => 'to_do',
                'attempt_number' => $attemptNumber,
            ]);
        });
    }

    /**
     * Review student submission (teacher functionality).
     */
    public function reviewSubmission(WritingSubmission $submission, array $reviewData): WritingReview
    {
        return DB::transaction(function () use ($submission, $reviewData) {
            $review = WritingReview::create([
                'id' => Str::uuid(),
                'submission_id' => $submission->id,
                'teacher_id' => Auth::id(),
                'score' => $reviewData['score'] ?? null,
                'comments' => $reviewData['comments'] ?? null,
                'feedback_json' => $reviewData['feedback_json'] ?? null,
                'reviewed_at' => now(),
            ]);

            // Update submission status
            $submission->update(['status' => 'reviewed']);

            return $review;
        });
    }

    /**
     * Mark submission as done (student acknowledges review).
     */
    public function markAsDone(WritingSubmission $submission): WritingSubmission
    {
        $submission->update(['status' => 'done']);
        return $submission;
    }

    /**
     * Get student dashboard data.
     */
    public function getStudentDashboard(): array
    {
        $studentId = Auth::id();

        // Get all assigned tasks for student
        $assignments = WritingTaskAssignment::whereHas('writingTask', function ($query) {
            $query->where('is_published', true);
        })
            ->whereHas('classroom.students', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->with([
                'writingTask',
                'writingTask.submissions' => function ($query) use ($studentId) {
                    $query->where('student_id', $studentId)->latest('attempt_number');
                }
            ])
            ->get();

        return $assignments->map(function ($assignment) {
            $task = $assignment->writingTask;
            $submission = $task->submissions->first();

            return [
                'task_id' => $task->id,
                'title' => $task->title,
                'due_date' => $task->due_date,
                'status' => $submission ? $submission->status : 'to_do',
                'score' => $submission && $submission->review ? $submission->review->score : null,
                'attempt_number' => $submission ? $submission->attempt_number : 0,
                'can_retake' => $task->allow_retake &&
                    $submission &&
                    $submission->status === 'reviewed' &&
                    (!$task->max_retake_attempts || $submission->attempt_number < $task->max_retake_attempts),
            ];
        })->toArray();
    }

    /**
     * Get teacher dashboard data.
     */
    public function getTeacherDashboard(): array
    {
        $teacherId = Auth::id();

        $tasks = WritingTask::where('creator_id', $teacherId)
            ->with(['assignments.classroom', 'submissions.student', 'submissions.review'])
            ->get();

        return $tasks->map(function ($task) {
            $totalSubmissions = $task->submissions->count();
            $reviewedSubmissions = $task->submissions->where('status', 'reviewed')->count();

            return [
                'task_id' => $task->id,
                'title' => $task->title,
                'created_at' => $task->created_at,
                'due_date' => $task->due_date,
                'is_published' => $task->is_published,
                'assigned_classes' => $task->assignments->count(),
                'total_submissions' => $totalSubmissions,
                'pending_reviews' => $totalSubmissions - $reviewedSubmissions,
            ];
        })->toArray();
    }

    /**
     * Count words in text content.
     */
    private function countWords(string $content): int
    {
        return str_word_count(strip_tags($content));
    }

    /**
     * Generate template for similar mistakes retake.
     */
    private function generateSimilarMistakesTemplate(WritingSubmission $submission): string
    {
        // This would analyze the previous submission and group similar mistakes
        // For now, return the original content with mistake markers
        return $submission->content ?? '';
    }

    /**
     * Generate template for chosen mistakes retake.
     */
    private function generateChosenMistakesTemplate(WritingSubmission $submission, array $chosenMistakes): string
    {
        // This would highlight only the chosen mistakes for correction
        // For now, return the original content
        return $submission->content ?? '';
    }
}