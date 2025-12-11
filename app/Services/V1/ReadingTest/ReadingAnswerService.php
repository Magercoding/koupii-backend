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
    public function submitAnswer(ReadingSubmission $submission, array $data): ReadingQuestionAnswer
    {
        if ($submission->status !== 'in_progress') {
            throw new Exception('Cannot submit answers for completed submission');
        }

        return DB::transaction(function () use ($submission, $data) {
            $answer = ReadingQuestionAnswer::where('submission_id', $submission->id)
                ->where('question_id', $data['question_id'])
                ->first();

            if (!$answer) {
                throw new Exception('Answer record not found');
            }

            $answer->update([
                'student_answer' => $data['answer'],
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
            if ($submission->status !== 'in_progress') {
                throw new Exception('Test has already been submitted');
            }

            // Update submission timing
            $submission->update([
                'submitted_at' => now(),
                'time_taken_seconds' => $data['time_taken_seconds'] ?? null,
                'status' => 'submitted'
            ]);

            // Process any remaining answers
            if (!empty($data['answers'])) {
                foreach ($data['answers'] as $answerData) {
                    $this->submitAnswer($submission, $answerData);
                }
            }

            // Calculate final score
            $submission->calculateScore();

            // Mark as completed
            $submission->update(['status' => 'completed']);

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
        // Get vocabularies that appear in test passages
        $testPassages = $submission->test->passages;
        
        foreach ($testPassages as $passage) {
            // Extract vocabulary words from passage content
            $vocabularies = $this->extractVocabulariesFromPassage($passage);
            
            foreach ($vocabularies as $vocabulary) {
                StudentVocabularyDiscovery::firstOrCreate([
                    'student_id' => $submission->student_id,
                    'test_id' => $submission->test_id,
                    'vocabulary_id' => $vocabulary->id,
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
        // This is a simplified implementation
        // In production, you might want to use NLP libraries for better word extraction
        
        $passageText = $passage->description ?? '';
        
        // Get vocabularies that contain words found in the passage
        $vocabularies = \App\Models\Vocabulary::where('is_public', true)
            ->get()
            ->filter(function ($vocab) use ($passageText) {
                return stripos($passageText, $vocab->word) !== false;
            });

        return $vocabularies->toArray();
    }
}