<?php

namespace App\Services\V1\Listening;

use App\Models\ListeningTask;
use App\Models\TestQuestion;
use App\Models\Test;
use App\Helpers\Listening\ListeningQuestionHelper;
use App\Helpers\ValidationHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListeningQuestionService
{
    /**
     * Get all questions for a listening task
     */
    public function getTaskQuestions(ListeningTask $listeningTask): Collection
    {
        return $listeningTask->test->questions()
            ->with(['questionOptions', 'audioSegments'])
            ->orderBy('order')
            ->get();
    }

    /**
     * Create a new question for listening task
     */
    public function createQuestion(ListeningTask $listeningTask, array $questionData): TestQuestion
    {
        // Validate question type
        $questionType = $questionData['question_type'];
        if (!$this->isValidQuestionType($questionType)) {
            throw new ValidationException('Invalid question type: ' . $questionType);
        }

        // Validate question data structure for specific type
        $this->validateQuestionData($questionType, $questionData);

        return DB::transaction(function () use ($listeningTask, $questionData) {
            // Get the next order number
            $nextOrder = $this->getNextQuestionOrder($listeningTask->test);

            // Create the question
            $question = TestQuestion::create([
                'test_id' => $listeningTask->test_id,
                'question_text' => $questionData['question_text'],
                'question_type' => $questionData['question_type'],
                'options' => $questionData['options'] ?? null,
                'correct_answer' => $questionData['correct_answer'],
                'points' => $questionData['points'] ?? 1,
                'order' => $questionData['order'] ?? $nextOrder,
                'time_limit' => $questionData['time_limit'] ?? null,
                'audio_segment' => $questionData['audio_segment'] ?? null,
                'instructions' => $questionData['instructions'] ?? null,
                'explanation' => $questionData['explanation'] ?? null
            ]);

            // Create question options if provided
            if (!empty($questionData['question_options'])) {
                $this->createQuestionOptions($question, $questionData['question_options']);
            }

            return $question->load(['questionOptions', 'audioSegments']);
        });
    }

    /**
     * Update an existing question
     */
    public function updateQuestion(TestQuestion $question, array $questionData): TestQuestion
    {
        // Validate question type if changing
        if (isset($questionData['question_type'])) {
            $questionType = $questionData['question_type'];
            if (!$this->isValidQuestionType($questionType)) {
                throw new ValidationException('Invalid question type: ' . $questionType);
            }
            
            // Validate question data structure for new type
            $this->validateQuestionData($questionType, $questionData);
        }

        return DB::transaction(function () use ($question, $questionData) {
            $question->update($questionData);

            // Update question options if provided
            if (isset($questionData['question_options'])) {
                $this->updateQuestionOptions($question, $questionData['question_options']);
            }

            return $question->fresh(['questionOptions', 'audioSegments']);
        });
    }

    /**
     * Delete a question
     */
    public function deleteQuestion(TestQuestion $question): bool
    {
        return DB::transaction(function () use ($question) {
            // Delete related question options
            $question->questionOptions()->delete();
            
            // Delete the question
            return $question->delete();
        });
    }

    /**
     * Duplicate a question
     */
    public function duplicateQuestion(TestQuestion $question, array $overrides = []): TestQuestion
    {
        return DB::transaction(function () use ($question, $overrides) {
            $questionData = $question->toArray();
            
            // Remove ID and timestamps
            unset($questionData['id'], $questionData['created_at'], $questionData['updated_at']);
            
            // Apply overrides
            $questionData = array_merge($questionData, $overrides);
            
            // Set new order if not specified
            if (!isset($questionData['order'])) {
                $questionData['order'] = $this->getNextQuestionOrder($question->test);
            }

            $newQuestion = TestQuestion::create($questionData);

            // Duplicate question options
            if ($question->questionOptions->count() > 0) {
                foreach ($question->questionOptions as $option) {
                    $newQuestion->questionOptions()->create([
                        'option_text' => $option->option_text,
                        'option_value' => $option->option_value,
                        'is_correct' => $option->is_correct,
                        'order' => $option->order
                    ]);
                }
            }

            return $newQuestion->load(['questionOptions', 'audioSegments']);
        });
    }

    /**
     * Reorder questions
     */
    public function reorderQuestions(Test $test, array $questionOrders): bool
    {
        return DB::transaction(function () use ($test, $questionOrders) {
            foreach ($questionOrders as $questionId => $order) {
                TestQuestion::where('id', $questionId)
                    ->where('test_id', $test->id)
                    ->update(['order' => $order]);
            }
            return true;
        });
    }

    /**
     * Bulk create questions
     */
    public function bulkCreateQuestions(ListeningTask $listeningTask, array $questionsData): array
    {
        $createdQuestions = [];
        $errors = [];

        DB::transaction(function () use ($listeningTask, $questionsData, &$createdQuestions, &$errors) {
            foreach ($questionsData as $index => $questionData) {
                try {
                    $question = $this->createQuestion($listeningTask, $questionData);
                    $createdQuestions[] = $question;
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'question_text' => $questionData['question_text'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }
        });

        return [
            'created' => $createdQuestions,
            'errors' => $errors,
            'created_count' => count($createdQuestions),
            'error_count' => count($errors)
        ];
    }

    /**
     * Get supported question types
     */
    public function getSupportedQuestionTypes(): array
    {
        return ListeningQuestionHelper::QUESTION_TYPES;
    }

    /**
     * Get question template for specific type
     */
    public function getQuestionTemplate(string $questionType): array
    {
        if (!$this->isValidQuestionType($questionType)) {
            throw new ValidationException('Invalid question type: ' . $questionType);
        }

        return ListeningQuestionHelper::getQuestionTemplate($questionType);
    }

    /**
     * Validate question data for specific type
     */
    public function validateQuestionData(string $questionType, array $questionData): bool
    {
        return ListeningQuestionHelper::validateQuestionData($questionType, $questionData);
    }

    /**
     * Preview question with rendered content
     */
    public function previewQuestion(TestQuestion $question): array
    {
        $preview = [
            'question' => $question->load(['questionOptions', 'audioSegments']),
            'rendered_content' => $this->renderQuestionContent($question),
            'validation_status' => $this->validateQuestionCompleteness($question),
            'type_info' => ListeningQuestionHelper::QUESTION_TYPES[$question->question_type] ?? null
        ];

        return $preview;
    }

    /**
     * Grade student answer for a question
     */
    public function gradeAnswer(TestQuestion $question, $studentAnswer): array
    {
        return ListeningQuestionHelper::gradeAnswer($question, $studentAnswer);
    }

    /**
     * Check if question type is valid
     */
    private function isValidQuestionType(string $questionType): bool
    {
        return array_key_exists($questionType, ListeningQuestionHelper::QUESTION_TYPES);
    }

    /**
     * Get next question order number
     */
    private function getNextQuestionOrder(Test $test): int
    {
        $maxOrder = TestQuestion::where('test_id', $test->id)->max('order');
        return ($maxOrder ?? 0) + 1;
    }

    /**
     * Create question options
     */
    private function createQuestionOptions(TestQuestion $question, array $optionsData): void
    {
        foreach ($optionsData as $optionData) {
            $question->questionOptions()->create($optionData);
        }
    }

    /**
     * Update question options
     */
    private function updateQuestionOptions(TestQuestion $question, array $optionsData): void
    {
        // Delete existing options
        $question->questionOptions()->delete();
        
        // Create new options
        $this->createQuestionOptions($question, $optionsData);
    }

    /**
     * Render question content for preview
     */
    private function renderQuestionContent(TestQuestion $question): array
    {
        return [
            'question_html' => $this->formatQuestionText($question),
            'options_html' => $this->formatQuestionOptions($question),
            'instructions_html' => $this->formatInstructions($question),
            'audio_info' => $this->getAudioInfo($question)
        ];
    }

    /**
     * Validate question completeness
     */
    private function validateQuestionCompleteness(TestQuestion $question): array
    {
        $errors = [];
        $warnings = [];

        // Required field checks
        if (empty($question->question_text)) {
            $errors[] = 'Question text is required';
        }

        if (empty($question->correct_answer)) {
            $errors[] = 'Correct answer is required';
        }

        // Type-specific validation
        $typeValidation = ListeningQuestionHelper::validateQuestionStructure($question);
        $errors = array_merge($errors, $typeValidation['errors'] ?? []);
        $warnings = array_merge($warnings, $typeValidation['warnings'] ?? []);

        return [
            'is_valid' => empty($errors),
            'is_complete' => empty($errors) && empty($warnings),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Format question text for display
     */
    private function formatQuestionText(TestQuestion $question): string
    {
        // Basic formatting - can be enhanced with rich text processing
        return nl2br(htmlspecialchars($question->question_text));
    }

    /**
     * Format question options for display
     */
    private function formatQuestionOptions(TestQuestion $question): array
    {
        if (!$question->questionOptions || $question->questionOptions->isEmpty()) {
            return [];
        }

        return $question->questionOptions->map(function ($option) {
            return [
                'id' => $option->id,
                'text' => $option->option_text,
                'value' => $option->option_value,
                'formatted_text' => nl2br(htmlspecialchars($option->option_text))
            ];
        })->toArray();
    }

    /**
     * Format instructions for display
     */
    private function formatInstructions(TestQuestion $question): ?string
    {
        if (empty($question->instructions)) {
            return null;
        }

        return nl2br(htmlspecialchars($question->instructions));
    }

    /**
     * Get audio information for question
     */
    private function getAudioInfo(TestQuestion $question): ?array
    {
        if (empty($question->audio_segment)) {
            return null;
        }

        return [
            'segment_info' => $question->audio_segment,
            'has_audio' => true,
            'duration' => $question->audio_segment['duration'] ?? null,
            'start_time' => $question->audio_segment['start_time'] ?? null,
            'end_time' => $question->audio_segment['end_time'] ?? null
        ];
    }
}