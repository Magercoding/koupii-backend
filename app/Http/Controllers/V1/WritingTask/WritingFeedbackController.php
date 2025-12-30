<?php

namespace App\Http\Controllers\V1\WritingTask;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\WritingTask\WritingFeedbackResource;
use App\Models\WritingFeedback;
use App\Models\WritingSubmission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WritingFeedbackController extends Controller
{
    /**
     * Create feedback for a submission
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'submission_id' => 'required|uuid|exists:writing_submissions,id',
            'question_id' => 'nullable|uuid|exists:writing_task_questions,id',
            'feedback_type' => 'required|in:overall,grammar,content,structure,vocabulary,coherence',
            'score' => 'nullable|numeric|min:0',
            'max_score' => 'nullable|numeric|min:0',
            'comments' => 'nullable|string',
            'detailed_feedback' => 'nullable|array',
            'suggestions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $submission = WritingSubmission::findOrFail($request->submission_id);
        
        // Check if teacher/admin has permission to grade
        $user = $request->user();
        if (!in_array($user->role, ['admin', 'teacher'])) {
            return response()->json([
                'message' => 'Unauthorized to provide feedback'
            ], 403);
        }

        // Create feedback
        $feedback = WritingFeedback::create([
            'submission_id' => $submission->id,
            'question_id' => $request->question_id,
            'feedback_type' => $request->feedback_type,
            'score' => $request->score,
            'max_score' => $request->max_score,
            'comments' => $request->comments,
            'detailed_feedback' => $request->detailed_feedback,
            'suggestions' => $request->suggestions,
            'graded_by' => $user->id,
        ]);

        // Update submission status if this is overall feedback
        if ($request->feedback_type === 'overall') {
            $submission->update(['status' => 'reviewed']);
        }

        return response()->json([
            'message' => 'Feedback created successfully',
            'data' => new WritingFeedbackResource($feedback->load(['submission', 'question', 'grader']))
        ], 201);
    }

    /**
     * Get feedback for a submission
     */
    public function getBySubmission(string $submissionId): JsonResponse
    {
        $submission = WritingSubmission::findOrFail($submissionId);
        
        $feedback = WritingFeedback::where('submission_id', $submission->id)
            ->with(['question', 'grader'])
            ->orderBy('feedback_type')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Feedback retrieved successfully',
            'data' => WritingFeedbackResource::collection($feedback)
        ]);
    }

    /**
     * Update feedback
     */
    public function update(Request $request, string $feedbackId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'score' => 'nullable|numeric|min:0',
            'max_score' => 'nullable|numeric|min:0',
            'comments' => 'nullable|string',
            'detailed_feedback' => 'nullable|array',
            'suggestions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $feedback = WritingFeedback::findOrFail($feedbackId);
        
        // Check if user is the original grader or admin
        $user = $request->user();
        if ($feedback->graded_by !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized to update this feedback'
            ], 403);
        }

        $feedback->update($request->only([
            'score',
            'max_score', 
            'comments',
            'detailed_feedback',
            'suggestions'
        ]));

        return response()->json([
            'message' => 'Feedback updated successfully',
            'data' => new WritingFeedbackResource($feedback->fresh())
        ]);
    }

    /**
     * Generate automated feedback (AI-powered)
     */
    public function generateAutomated(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'submission_id' => 'required|uuid|exists:writing_submissions,id',
            'feedback_types' => 'nullable|array',
            'feedback_types.*' => 'in:grammar,vocabulary,structure,coherence',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $submission = WritingSubmission::with(['question', 'attempt.writingTask'])
            ->findOrFail($request->submission_id);

        $feedbackTypes = $request->feedback_types ?? ['grammar', 'vocabulary', 'structure'];
        $generatedFeedback = [];

        foreach ($feedbackTypes as $type) {
            // This is where you'd integrate with AI services like OpenAI, Grammarly API, etc.
            $automatedFeedback = $this->generateAIFeedback($submission->content, $type);
            
            $feedback = WritingFeedback::create([
                'submission_id' => $submission->id,
                'feedback_type' => $type,
                'score' => $automatedFeedback['score'],
                'max_score' => 10, // Default max score for automated feedback
                'comments' => $automatedFeedback['comments'],
                'detailed_feedback' => $automatedFeedback['details'],
                'suggestions' => $automatedFeedback['suggestions'],
                'graded_by' => null, // Null indicates automated feedback
            ]);

            $generatedFeedback[] = new WritingFeedbackResource($feedback);
        }

        return response()->json([
            'message' => 'Automated feedback generated successfully',
            'data' => $generatedFeedback
        ]);
    }

    /**
     * Get feedback summary for an attempt
     */
    public function getAttemptSummary(string $attemptId): JsonResponse
    {
        $feedback = WritingFeedback::whereHas('submission', function ($query) use ($attemptId) {
            $query->where('attempt_id', $attemptId);
        })->with(['submission', 'question'])->get();

        $summary = [
            'total_submissions' => $feedback->groupBy('submission_id')->count(),
            'feedback_by_type' => $feedback->groupBy('feedback_type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'average_score' => $group->avg('score'),
                    'average_percentage' => $group->avg('percentage_score'),
                ];
            }),
            'overall_score' => $feedback->where('feedback_type', 'overall')->avg('score'),
            'overall_percentage' => $feedback->where('feedback_type', 'overall')->avg('percentage_score'),
            'grade_level' => $this->calculateGradeLevel($feedback->where('feedback_type', 'overall')->avg('percentage_score')),
        ];

        return response()->json([
            'message' => 'Feedback summary retrieved successfully',
            'data' => $summary
        ]);
    }

    /**
     * Mock AI feedback generation (replace with actual AI service)
     */
    private function generateAIFeedback(string $content, string $type): array
    {
        // This is a mock implementation. In production, integrate with:
        // - OpenAI API for content analysis
        // - Grammarly API for grammar checking
        // - Custom NLP services for specific feedback types
        
        $wordCount = str_word_count($content);
        
        return match($type) {
            'grammar' => [
                'score' => rand(6, 9),
                'comments' => 'Good use of grammar overall. Consider reviewing verb tenses in some sentences.',
                'details' => [
                    'grammar_errors' => rand(0, 3),
                    'suggestions_count' => rand(1, 5),
                ],
                'suggestions' => [
                    'Check subject-verb agreement in longer sentences',
                    'Review use of articles (a, an, the)',
                ]
            ],
            'vocabulary' => [
                'score' => rand(5, 8),
                'comments' => 'Good vocabulary range. Try using more varied and sophisticated terms.',
                'details' => [
                    'unique_words' => $wordCount * 0.7,
                    'advanced_words' => rand(5, 15),
                ],
                'suggestions' => [
                    'Use more descriptive adjectives',
                    'Incorporate academic vocabulary',
                ]
            ],
            'structure' => [
                'score' => rand(6, 9),
                'comments' => 'Clear paragraph structure. Consider strengthening transitions between ideas.',
                'details' => [
                    'paragraph_count' => substr_count($content, '\n\n') + 1,
                    'transition_score' => rand(6, 9),
                ],
                'suggestions' => [
                    'Add transition phrases between paragraphs',
                    'Ensure each paragraph has a clear main idea',
                ]
            ],
            default => [
                'score' => rand(5, 8),
                'comments' => 'General feedback generated.',
                'details' => [],
                'suggestions' => [],
            ]
        };
    }

    /**
     * Calculate grade level from percentage
     */
    private function calculateGradeLevel(float $percentage): string
    {
        if ($percentage >= 90) return 'A+';
        if ($percentage >= 85) return 'A';
        if ($percentage >= 80) return 'A-';
        if ($percentage >= 75) return 'B+';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 65) return 'B-';
        if ($percentage >= 60) return 'C+';
        if ($percentage >= 55) return 'C';
        if ($percentage >= 50) return 'C-';
        return 'F';
    }
}