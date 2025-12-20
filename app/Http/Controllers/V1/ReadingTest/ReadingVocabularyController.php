<?php

namespace App\Http\Controllers\V1\ReadingTest;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ReadingTest\VocabularyDiscoveryResource;
use App\Services\V1\ReadingTest\ReadingVocabularyService;
use App\Models\ReadingSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReadingVocabularyController extends Controller implements HasMiddleware
{
    public function __construct(
        private ReadingVocabularyService $vocabularyService
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    /**
     * Get discovered vocabularies from a test
     */
    public function getDiscoveredVocabularies(ReadingSubmission $submission): JsonResponse
    {
        try {
            // Check ownership
            if ($submission->student_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $vocabularies = $this->vocabularyService->getDiscoveredVocabularies($submission);

            return response()->json([
                'success' => true,
                'data' => VocabularyDiscoveryResource::collection($vocabularies)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get vocabularies',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save vocabulary to student's bank
     */
    public function saveVocabulary(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'vocabulary_id' => 'required|exists:vocabularies,id',
                'test_id' => 'required|exists:tests,id'
            ]);

            $bankEntry = $this->vocabularyService->saveVocabularyToBank(
                auth()->id(),
                $request->vocabulary_id,
                $request->test_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Vocabulary saved to your bank',
                'data' => $bankEntry
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save vocabulary',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get student's vocabulary bank
     */
    public function getVocabularyBank(Request $request): JsonResponse
    {
        try {
            $vocabularies = $this->vocabularyService->getStudentVocabularyBank(
                auth()->id(),
                $request->all()
            );

            return response()->json([
                'success' => true,
                'data' => $vocabularies
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get vocabulary bank',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Practice vocabulary
     */
    public function practiceVocabulary(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'vocabulary_id' => 'required|exists:vocabularies,id'
            ]);

            $this->vocabularyService->practiceVocabulary(
                auth()->id(),
                $request->vocabulary_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Practice session recorded'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to record practice',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}