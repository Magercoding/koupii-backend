<?php

namespace App\Services\V1\ReadingTest;

use App\Models\ReadingSubmission;
use App\Models\ReadingQuestionAnswer;
use App\Models\TestQuestion;
use App\Models\StudentVocabularyDiscovery;
use Illuminate\Support\Facades\DB;
use Exception;

class ReadingAnswerService
{
    /**
     * Submit answer for a specific question
     */
    public function submitAnswer(ReadingSubmission $submission, array $data, bool $isFinal = false): ReadingQuestionAnswer
    {
        if (!$isFinal && !in_array($submission->status, ['in_progress', 'submitted', 'completed'])) {
            throw new Exception('Cannot submit answers for this submission');
        }

        return DB::transaction(function () use ($submission, $data) {
            $questionId = $data['question_id'] ?? null;

            // Try to find existing answer record by any matching identifier
            $answer = ReadingQuestionAnswer::where('submission_id', $submission->id)
                ->where(function($q) use ($questionId) {
                    $q->where('question_id', $questionId)
                      ->orWhere('reading_task_question_id', $questionId);
                })
                ->first();

            if (!$answer) {
                // Create on the fly
                $answer = ReadingQuestionAnswer::create([
                    'submission_id' => $submission->id,
                    'question_id' => null,
                    'reading_task_question_id' => $questionId,
                    'student_answer' => null,
                    'correct_answer' => null,
                    'is_correct' => null,
                    'points_earned' => 0,
                ]);
            }

            $answer->update([
                'student_answer' => $data['answer'] ?? null,
                'time_spent_seconds' => $data['time_spent_seconds'] ?? null,
            ]);

            // Check if answer is correct
            $answer->checkAnswer();

            return $answer;
        });
    }

    /**
     * Submit the entire test
     */
    public function submitTest(ReadingSubmission $submission, array $data): ReadingSubmission
    {
        return DB::transaction(function () use ($submission, $data) {
            // Process any remaining answers first while still in_progress
            if (!empty($data['answers'])) {
                foreach ($data['answers'] as $answerData) {
                    $this->submitAnswer($submission, $answerData, true);
                }
            }

            // Now update submission status and metadata
            $submission->update([
                'submitted_at' => $submission->submitted_at ?: now(),
                'time_taken_seconds' => $data['time_taken_seconds'] ?? $submission->time_taken_seconds,
                'status' => 'submitted'
            ]);

            // Calculate final score
            $submission->calculateScore();
            $submission->refresh(); // Critically refresh to get calculated percentage into memory

            // Mark as completed
            $submission->update(['status' => 'completed']);

            // Sync with StudentAssignment
            $assignmentId = $submission->assignment_id;
            if ($assignmentId) {
                $studentAssignment = \App\Models\StudentAssignment::where('assignment_id', $assignmentId)
                    ->where('student_id', $submission->student_id)
                    ->first();

                if ($studentAssignment) {
                    $studentAssignment->update([
                        'status' => \App\Models\StudentAssignment::STATUS_SUBMITTED,
                        'score' => $submission->percentage ?? 0,
                        'completed_at' => now(),
                        'last_activity_at' => now(),
                    ]);
                }
            }

            // Discover vocabularies
            $this->discoverVocabularies($submission);

            return $submission->fresh(['answers', 'test']);
        });
    }

    /**
     * Get results with explanations and highlights
     */
    public function getResultsWithExplanations(ReadingSubmission $submission): array
    {
        $answers = $submission->answers()->with([
            'question.highlightSegments',
            'question.questionGroup.passage',
            'question.questionOptions'
        ])->get();

        $results = [
            'submission_summary' => [
                'total_score' => $submission->total_score,
                'percentage' => $submission->percentage,
                'grade' => $submission->grade,
                'total_correct' => $submission->total_correct,
                'total_incorrect' => $submission->total_incorrect,
                'total_unanswered' => $submission->total_unanswered,
                'time_taken' => $submission->time_taken_seconds,
            ],
            'question_results' => []
        ];

        foreach ($answers as $answer) {
            $questionResult = [
                'question_id' => $answer->question_id,
                'question_text' => $answer->question->question_text,
                'question_type' => $answer->question->question_type,
                'student_answer' => $answer->student_answer,
                'correct_answer' => $answer->correct_answer,
                'is_correct' => $answer->is_correct,
                'points_earned' => $answer->points_earned,
                'max_points' => $answer->question->points_value,
                'explanations' => [],
                'highlights' => []
            ];

            // Add highlight segments for explanation
            foreach ($answer->question->highlightSegments as $highlight) {
                $questionResult['highlights'][] = [
                    'text' => $highlight->highlighted_text,
                    'explanation' => $highlight->explanation,
                    'color' => $highlight->highlight_color,
                    'start_position' => $highlight->start_position,
                    'end_position' => $highlight->end_position,
                ];
            }

            $results['question_results'][] = $questionResult;
        }

        return $results;
    }

    /**
     * Discover vocabularies from the test content
     */
    private function discoverVocabularies(ReadingSubmission $submission): void
    {
        $testPassages = [];
        
        // Handle both new ReadingTask and legacy Test
        if ($submission->reading_task_id && $submission->readingTask) {
            $testPassages = $submission->readingTask->passages ?? [];
        } elseif ($submission->test_id && $submission->test) {
            $testPassages = $submission->test->passages ?? [];
        }
        
        foreach ($testPassages as $passage) {
            // Extract vocabulary words from passage content
            $vocabularies = $this->extractVocabulariesFromPassage($passage);
            
            foreach ($vocabularies as $vocabularyArray) {
                \App\Models\StudentVocabularyDiscovery::firstOrCreate([
                    'student_id' => $submission->student_id,
                    'test_id' => $submission->test_id,
                    'vocabulary_id' => $vocabularyArray['id'],
                ], [
                    'discovered_at' => now(),
                    'is_saved' => false,
                ]);
            }
        }
    }

    /**
     * Extract vocabularies from passage content
     */
    private function extractVocabulariesFromPassage($passage): array
    {
        // Handle both object (legacy) and array (new JSON task)
        $passageText = is_array($passage) ? ($passage['description'] ?? '') : ($passage->description ?? '');
        
        if (empty($passageText)) return [];

        // Get vocabularies that contain words found in the passage
        $vocabularies = \App\Models\Vocabulary::where('is_public', true)
            ->get()
            ->filter(function ($vocab) use ($passageText) {
                return stripos($passageText, $vocab->word) !== false;
            });

        return $vocabularies->toArray();
    }
}