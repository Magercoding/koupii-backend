<?php

namespace App\Services\V1\SpeakingTask;

use Google\Cloud\Speech\V1\SpeechClient;
use Google\Cloud\Speech\V1\RecognitionAudio;
use Google\Cloud\Speech\V1\RecognitionConfig;
use Google\Cloud\Speech\V1\RecognitionConfig\AudioEncoding;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SpeechToTextService
{
    private SpeechClient $speechClient;

    public function __construct()
    {
        // Initialize Google Speech client
        $this->speechClient = new SpeechClient([
            'credentials' => config('services.google.speech_credentials_path')
        ]);
    }

    /**
     * Convert audio file to text using Google Speech-to-Text API
     */
    public function convertAudioToText(string $audioFilePath, string $languageCode = 'en-US'): array
    {
        try {
            // Read audio file
            $audioContent = Storage::get($audioFilePath);

            // Create recognition audio object
            $audio = (new RecognitionAudio())
                ->setContent($audioContent);

            // Configure recognition settings
            $config = (new RecognitionConfig())
                ->setEncoding(AudioEncoding::WEBM_OPUS) // or MP3, WAV based on your format
                ->setSampleRateHertz(48000) // Adjust based on your audio
                ->setLanguageCode($languageCode)
                ->setEnableAutomaticPunctuation(true)
                ->setEnableWordTimeOffsets(true)
                ->setMaxAlternatives(1);

            // Perform speech recognition
            $response = $this->speechClient->recognize($config, $audio);

            $transcriptions = [];
            $confidence = 0;
            $words = [];

            foreach ($response->getResults() as $result) {
                $alternatives = $result->getAlternatives();
                if ($alternatives->count() > 0) {
                    $alternative = $alternatives[0];
                    $transcriptions[] = $alternative->getTranscript();
                    $confidence = max($confidence, $alternative->getConfidence());

                    // Extract word-level timestamps
                    foreach ($alternative->getWords() as $word) {
                        $words[] = [
                            'word' => $word->getWord(),
                            'start_time' => $word->getStartTime()->getSeconds() + $word->getStartTime()->getNanos() / 1e9,
                            'end_time' => $word->getEndTime()->getSeconds() + $word->getEndTime()->getNanos() / 1e9,
                        ];
                    }
                }
            }

            return [
                'success' => true,
                'transcript' => implode(' ', $transcriptions),
                'confidence' => $confidence,
                'words' => $words,
                'word_count' => count($words),
                'duration' => !empty($words) ? end($words)['end_time'] : 0
            ];

        } catch (Exception $e) {
            Log::error('Speech-to-text conversion failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to convert speech to text',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Analyze speech quality metrics
     */
    public function analyzeSpeechQuality(array $speechData): array
    {
        $words = $speechData['words'] ?? [];
        $duration = $speechData['duration'] ?? 0;
        $wordCount = count($words);

        // Calculate speaking rate (words per minute)
        $speakingRate = $duration > 0 ? ($wordCount / $duration) * 60 : 0;

        // Calculate pause analysis
        $pauses = [];
        $totalPauseTime = 0;

        for ($i = 1; $i < count($words); $i++) {
            $pauseDuration = $words[$i]['start_time'] - $words[$i-1]['end_time'];
            if ($pauseDuration > 0.3) { // Pauses longer than 300ms
                $pauses[] = $pauseDuration;
                $totalPauseTime += $pauseDuration;
            }
        }

        // Calculate fluency metrics
        $averagePauseDuration = count($pauses) > 0 ? $totalPauseTime / count($pauses) : 0;
        $pauseFrequency = $duration > 0 ? count($pauses) / $duration : 0;

        // Calculate confidence level
        $confidence = $speechData['confidence'] ?? 0;

        return [
            'speaking_rate' => round($speakingRate, 2), // words per minute
            'word_count' => $wordCount,
            'duration' => round($duration, 2),
            'pause_count' => count($pauses),
            'total_pause_time' => round($totalPauseTime, 2),
            'average_pause_duration' => round($averagePauseDuration, 2),
            'pause_frequency' => round($pauseFrequency, 2), // pauses per second
            'confidence_score' => round($confidence * 100, 2),
            'fluency_score' => $this->calculateFluencyScore($speakingRate, $pauseFrequency, $confidence),
        ];
    }

    /**
     * Calculate overall fluency score based on various metrics
     */
    private function calculateFluencyScore(float $speakingRate, float $pauseFrequency, float $confidence): float
    {
        // Ideal speaking rate for English: 140-160 words per minute
        $rateScore = 100;
        if ($speakingRate < 100 || $speakingRate > 200) {
            $rateScore = max(0, 100 - abs($speakingRate - 150) * 2);
        }

        // Lower pause frequency is better (less than 0.5 pauses per second)
        $pauseScore = max(0, 100 - $pauseFrequency * 100);

        // Higher confidence is better
        $confidenceScore = $confidence * 100;

        // Weighted average
        $fluencyScore = ($rateScore * 0.4) + ($pauseScore * 0.3) + ($confidenceScore * 0.3);

        return round($fluencyScore, 2);
    }

    /**
     * Clean up temporary audio files
     */
    public function cleanup(): void
    {
        $this->speechClient->close();
    }
}