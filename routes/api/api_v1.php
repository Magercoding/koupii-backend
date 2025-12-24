<?php

use Illuminate\Support\Facades\Route;
/**
 * API V1 Routes
 */

Route::get('/health', fn() => response()->json(['ok' => true, 'time' => time()]));

Route::get('/version', function () {
    $version = [
        'app' => config('app.name', 'Koupii API'),
        'version' => '1.0.0',
        'environment' => config('app.env'),
        'timestamp' => now()->toISOString(),
    ];

    // Add git commit if available
    if (file_exists(base_path('.git/HEAD'))) {
        $head = trim(file_get_contents(base_path('.git/HEAD')));
        if (strpos($head, 'ref:') === 0) {
            $ref = trim(substr($head, 4));
            $commitFile = base_path('.git/' . $ref);
            if (file_exists($commitFile)) {
                $version['commit'] = substr(trim(file_get_contents($commitFile)), 0, 7);
            }
        } else {
            $version['commit'] = substr($head, 0, 7);
        }
    }

    return response()->json($version);
});


/**
 * Authentication 
 */
require __DIR__ . '/../v1/auth.php';
/**
 * oauth Authentication 
 */
require __DIR__ . '/../v1/oauth.php';
/**
 * User Management
 */
require __DIR__ . '/../v1/user.php';

/**
 * vocabulary Management
 */
require __DIR__ . '/../v1/vocabulary.php';
/**
 * class Management
 */
require __DIR__ . '/../v1/class.php';
/**
 * Reading Test Question Management
 */
require __DIR__ . '/../v1/reading_test_question.php';
/**
 * Reading Test Management
 */
require __DIR__ . '/../v1/reading_test.php';
/**
 * Reading Task Management
 */
require __DIR__ . '/../v1/reading_task.php';

/**
 * Reading Dashboard, Reviews & Assignments
 */
// require __DIR__ . '/../v1/reading_dashboard.php';

/**
 * Writing Task Management
 */
require __DIR__ . '/../v1/writing_task.php';
/**
 * Writing Test Management
 */
require __DIR__ . '/../v1/writing_test.php';

/**
 * Listening Test Management
 */
require __DIR__ . '/../v1/listening_test.php';

/**
 * Listening Tasks Management
 */
require __DIR__ . '/../v1/listening.php';

/**
 * Listening Task Reviews, Assignments & Dashboard
 */
require __DIR__ . '/../v1/listening_task.php';

/**
 * Speaking Task Management
 */
require __DIR__ . '/../v1/speaking.php';

/**
 * Test System Management (Main test system)
 */
require __DIR__ . '/../v1/test.php';
