<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Speaking Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the speaking module including speech-to-text
    | processing, file uploads, and Google Cloud Speech API integration.
    |
    */

    // Storage configuration
    'storage' => [
        'disk' => env('SPEAKING_RECORDINGS_DISK', 'speaking_recordings'),
        'path' => env('SPEAKING_RECORDINGS_PATH', 'speaking_recordings'),
        'max_file_size' => env('SPEAKING_MAX_FILE_SIZE', 51200), // KB (50MB)
        'allowed_extensions' => explode(',', env('SPEAKING_ALLOWED_EXTENSIONS', 'mp3,wav,m4a,aac,ogg,webm')),
        'max_duration' => env('SPEAKING_MAX_DURATION', 1800), // seconds (30 minutes)
    ],

    // Google Cloud Speech-to-Text configuration
    'speech_to_text' => [
        'enabled' => env('GOOGLE_CLOUD_SPEECH_ENABLED', true),
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
        'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'language_code' => env('GOOGLE_CLOUD_SPEECH_LANGUAGE_CODE', 'en-US'),
        'model' => env('GOOGLE_CLOUD_SPEECH_MODEL', 'latest_long'),
        'use_enhanced' => env('GOOGLE_CLOUD_SPEECH_USE_ENHANCED', true),
        'enable_automatic_punctuation' => env('GOOGLE_CLOUD_SPEECH_AUTO_PUNCTUATION', true),
        'enable_word_time_offsets' => env('GOOGLE_CLOUD_SPEECH_WORD_OFFSETS', true),
        'profanity_filter' => env('GOOGLE_CLOUD_SPEECH_PROFANITY_FILTER', false),
    ],

    // Speech quality analysis settings
    'quality_analysis' => [
        'fluency' => [
            'pause_threshold' => 0.5, // seconds
            'long_pause_threshold' => 2.0, // seconds
            'max_pause_penalty' => 20, // percentage points
        ],
        'speaking_rate' => [
            'optimal_min' => 120, // words per minute
            'optimal_max' => 180, // words per minute
            'slow_threshold' => 90, // words per minute
            'fast_threshold' => 200, // words per minute
        ],
        'confidence' => [
            'min_acceptable' => 0.6, // minimum confidence score
            'good_threshold' => 0.8, // good confidence threshold
        ],
    ],

    // Processing configuration
    'processing' => [
        'timeout' => env('SPEAKING_PROCESSING_TIMEOUT', 300), // seconds (5 minutes)
        'retry_attempts' => env('SPEAKING_PROCESSING_RETRY', 3),
        'chunk_size' => env('SPEAKING_CHUNK_SIZE', 1024 * 1024), // 1MB chunks for large files
        'async_processing' => env('SPEAKING_ASYNC_PROCESSING', true),
    ],

    // Scoring configuration
    'scoring' => [
        'pronunciation_weight' => 0.25,
        'fluency_weight' => 0.25,
        'grammar_weight' => 0.20,
        'vocabulary_weight' => 0.15,
        'content_weight' => 0.15,
    ],

    // Review configuration
    'review' => [
        'auto_review_threshold' => env('SPEAKING_AUTO_REVIEW_THRESHOLD', 0.9), // confidence threshold for auto-review
        'review_timeout_hours' => env('SPEAKING_REVIEW_TIMEOUT', 72), // hours before review reminder
        'max_review_score' => 100,
        'passing_score' => 60,
    ],
];