<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Listening\LogAudioPlayRequest;
use App\Http\Resources\V1\Listening\ListeningAudioLogResource;
use App\Models\ListeningSubmission;
use App\Services\V1\Listening\ListeningAudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ListeningAudioPlayController extends Controller
{
    public function __construct(
        private ListeningAudioService $audioService
    ) {}

    /**
     * Log audio play interaction
     */
    public function logPlay(LogAudioPlayRequest $request, ListeningSubmission $submission): JsonResponse
    {
        try {
            $log = $this->audioService->logAudioPlay($submission, $request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Audio play logged successfully',
                'data' => new ListeningAudioLogResource($log)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get audio play logs
     */
    public function getLogs(ListeningSubmission $submission): JsonResponse
    {
        $logs = $submission->audioLogs()
            ->with(['audioSegment'])
            ->orderBy('played_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => ListeningAudioLogResource::collection($logs),
            'message' => 'Audio play logs retrieved successfully'
        ]);
    }

    /**
     * Get audio interaction statistics
     */
    public function getStats(ListeningSubmission $submission): JsonResponse
    {
        try {
            $stats = $this->audioService->getAudioStats($submission);

            return response()->json([
                'status' => 'success',
                'data' => $stats,
                'message' => 'Audio statistics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve audio statistics: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Export audio analytics data
     */
    public function exportAnalytics(ListeningSubmission $submission): JsonResponse
    {
        try {
            $analytics = $this->audioService->exportAnalytics($submission);

            return response()->json([
                'status' => 'success',
                'data' => $analytics,
                'message' => 'Audio analytics exported successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export audio analytics: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get audio segment analytics
     */
    public function getSegmentAnalytics(string $segmentId): JsonResponse
    {
        try {
            $analytics = $this->audioService->getSegmentAnalytics($segmentId);

            return response()->json([
                'status' => 'success',
                'data' => $analytics,
                'message' => 'Segment analytics retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve segment analytics: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}