<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Models\WritingSubmission;
use App\Models\SpeakingSubmission;
use App\Models\ReadingSubmission;
use App\Models\ListeningSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserSubmissionController extends Controller
{
    /**
     * Get all discover test results for the authenticated student.
     */
    public function getDiscoverResults(Request $request): JsonResponse
    {
        $studentId = Auth::id();

        // Writing Submissions
        $writing = WritingSubmission::with(['writingTask', 'review'])
            ->where('student_id', $studentId)
            ->whereNull('assignment_id')
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'type' => 'writing',
                    'title' => $s->writingTask?->title ?? 'Writing Test',
                    'status' => $s->status,
                    'score' => $s->review?->score ?? $s->score,
                    'submitted_at' => $s->submitted_at,
                    'feedback' => $s->review?->comments,
                ];
            });

        // Speaking Submissions
        $speaking = SpeakingSubmission::with(['speakingTask'])
            ->where('student_id', $studentId)
            ->whereNull('assignment_id')
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'type' => 'speaking',
                    'title' => $s->speakingTask?->title ?? 'Speaking Test',
                    'status' => $s->status,
                    'score' => $s->score,
                    'submitted_at' => $s->submitted_at,
                    'feedback' => $s->review_feedback, // Assume speaking uses this or similar
                ];
            });

        // Combine and sort
        $results = $writing->concat($speaking)
            ->sortByDesc('submitted_at')
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $results
        ]);
    }
}
