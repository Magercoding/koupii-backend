<?php

namespace App\Http\Controllers\V1\Listening;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Listening\LogAudioPlayRequest;
use App\Http\Resources\V1\Listening\ListeningAudioLogResource;
use App\Models\ListeningSubmission;
use App\Models\ListeningAudioSegment;
use App\Services\V1\Listening\ListeningAudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ListeningAudioController extends Controller
{
    public function __construct(
        private ListeningAudioService $audioService
    ) {}

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

    public function getLogs(ListeningSubmission $submission): JsonResponse
    {
        $logs = $submission->audioLogs()
            ->with(['audioSegment'])
            ->orderBy('played_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => ListeningAudioLogResource::collection($logs)
        ]);
    }

    public function getSegment(ListeningAudioSegment $segment): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $segment->id,
                'passage_id' => $segment->passage_id,
                'audio_url' => $segment->audio_url,
                'start_time' => $segment->start_time,
                'end_time' => $segment->end_time,
                'duration' => $segment->duration,
                'transcript' => $segment->transcript,
                'order' => $segment->order
            ]
        ]);
    }

    public function getStats(ListeningSubmission $submission): JsonResponse
    {
        $stats = $this->audioService->getAudioStats($submission);

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }
}