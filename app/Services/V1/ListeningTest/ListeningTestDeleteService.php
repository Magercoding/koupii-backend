<?php

namespace App\Services\V1\ListeningTest;

use App\Models\Test;
use App\Models\ListeningAudioSegment;
use App\Models\ListeningQuestion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ListeningTestDeleteService
{
    /**
     * Delete a listening test and all related data.
     */
    public function deleteTest(string $id): array
    {
        $test = Test::find($id);
        
        if (!$test) {
            return ['error' => 'Test not found', 'status' => 404];
        }

        if (!Auth::user()->isAdmin() && $test->creator_id !== Auth::id()) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        if ($test->type !== 'listening') {
            return ['error' => 'Invalid test type', 'status' => 400];
        }

        try {
            DB::transaction(function () use ($test) {
                // Delete audio files from storage
                $audioSegments = ListeningAudioSegment::where('test_id', $test->id)->get();
                foreach ($audioSegments as $segment) {
                    if ($segment->audio_url && Storage::exists($segment->audio_url)) {
                        Storage::delete($segment->audio_url);
                    }
                }

                // Delete related questions first
                ListeningQuestion::whereIn('audio_segment_id', 
                    $audioSegments->pluck('id')
                )->delete();

                // Delete audio segments
                ListeningAudioSegment::where('test_id', $test->id)->delete();

                // Delete submissions and related data
                $test->submissions()->delete();

                // Delete the test
                $test->delete();
            });

            return ['message' => 'Listening test deleted successfully', 'status' => 200];
        } catch (\Exception $e) {
            return ['error' => 'Failed to delete test: ' . $e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Delete an audio segment and its questions.
     */
    public function deleteAudioSegment(string $id): array
    {
        $segment = ListeningAudioSegment::find($id);
        
        if (!$segment) {
            return ['error' => 'Audio segment not found', 'status' => 404];
        }

        $test = Test::find($segment->test_id);
        if (!Auth::user()->isAdmin() && $test->creator_id !== Auth::id()) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        try {
            DB::transaction(function () use ($segment) {
                // Delete related questions
                ListeningQuestion::where('audio_segment_id', $segment->id)->delete();

                // Delete audio file from storage
                if ($segment->audio_url && Storage::exists($segment->audio_url)) {
                    Storage::delete($segment->audio_url);
                }

                // Delete the segment
                $segment->delete();
            });

            return ['message' => 'Audio segment deleted successfully', 'status' => 200];
        } catch (\Exception $e) {
            return ['error' => 'Failed to delete audio segment: ' . $e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Delete a listening question.
     */
    public function deleteQuestion(string $id): array
    {
        $question = ListeningQuestion::find($id);
        
        if (!$question) {
            return ['error' => 'Question not found', 'status' => 404];
        }

        $segment = ListeningAudioSegment::find($question->audio_segment_id);
        $test = Test::find($segment->test_id);
        
        if (!Auth::user()->isAdmin() && $test->creator_id !== Auth::id()) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        try {
            $question->delete();
            return ['message' => 'Question deleted successfully', 'status' => 200];
        } catch (\Exception $e) {
            return ['error' => 'Failed to delete question: ' . $e->getMessage(), 'status' => 500];
        }
    }

    /**
     * Clean up orphaned audio files.
     */
    public function cleanupOrphanedFiles(): array
    {
        if (!Auth::user()->isAdmin()) {
            return ['error' => 'Unauthorized', 'status' => 403];
        }

        try {
            $deletedCount = 0;
            $audioFiles = Storage::files('listening/audio');
            
            foreach ($audioFiles as $filePath) {
                $exists = ListeningAudioSegment::where('audio_url', $filePath)->exists();
                if (!$exists) {
                    Storage::delete($filePath);
                    $deletedCount++;
                }
            }

            return [
                'message' => "Cleaned up {$deletedCount} orphaned audio files", 
                'status' => 200,
                'deleted_count' => $deletedCount
            ];
        } catch (\Exception $e) {
            return ['error' => 'Failed to cleanup files: ' . $e->getMessage(), 'status' => 500];
        }
    }
}