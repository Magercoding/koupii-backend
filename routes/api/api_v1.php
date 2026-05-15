<?php

use Illuminate\Support\Facades\Route;
/**
 * API V1 Routes
 */

/**
 * @unauthenticated
 */
Route::get('/health', fn() => response()->json(['ok' => true, 'time' => time()]));

Route::get('/audio/{path}', function (string $path) {
    // Try local public storage first
    $fullPath = storage_path('app/public/' . $path);

    if (file_exists($fullPath)) {
        return response()->file($fullPath, [
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    // Fall back to R2/cloud storage — generate a temporary signed URL and redirect
    try {
        $disk = \Illuminate\Support\Facades\Storage::disk(config('filesystems.default', 'public'));
        if ($disk->exists($path)) {
            // Generate a signed URL valid for 1 hour
            $signedUrl = $disk->temporaryUrl($path, now()->addHour());
            return redirect($signedUrl);
        }
    } catch (\Exception $e) {
        // ignore and fall through to 404
    }

    abort(404, 'Audio file not found');
})->where('path', '.*');

/**
 * Public image proxy route — serves question/passage images from local storage
 * or generates a temporary signed URL for R2/cloud-stored images.
 *
 * Accepts ?url=<encoded-full-url> for R2 URLs, or /{path} for relative paths.
 * @unauthenticated
 */
Route::get('/images', function (\Illuminate\Http\Request $request) {
    $r2Bucket = env('CLOUDFLARE_R2_BUCKET', '');
    $rawUrl   = $request->query('url', '');
    $path     = $request->query('path', '');

    if ($rawUrl) {
        $parsed    = parse_url($rawUrl);
        $objectKey = ltrim($parsed['path'] ?? '', '/');
        if ($r2Bucket && str_starts_with($objectKey, $r2Bucket . '/')) {
            $objectKey = substr($objectKey, strlen($r2Bucket) + 1);
        }
        $path = $objectKey;
    }

    if (!$path) {
        abort(400, 'Missing image path or url parameter');
    }

    $fullPath = storage_path('app/public/' . $path);
    if (file_exists($fullPath)) {
        return response()->file($fullPath, ['Cache-Control' => 'public, max-age=3600']);
    }

    try {
        $disk = \Illuminate\Support\Facades\Storage::disk(config('filesystems.default', 'public'));
        if ($disk->exists($path)) {
            return redirect($disk->temporaryUrl($path, now()->addHour()));
        }
    } catch (\Exception $e) {
        // fall through
    }

    abort(404, 'Image not found');
});

Route::get('/images/{path}', function (string $path) {
    $r2Bucket = env('CLOUDFLARE_R2_BUCKET', '');
    $decoded  = urldecode($path);

    if (str_starts_with($decoded, 'http://') || str_starts_with($decoded, 'https://')) {
        $parsed = parse_url($decoded);
        $objectKey = ltrim($parsed['path'] ?? '', '/');
        if ($r2Bucket && str_starts_with($objectKey, $r2Bucket . '/')) {
            $objectKey = substr($objectKey, strlen($r2Bucket) + 1);
        }
        $path = $objectKey;
    } else {
        $path = $decoded;
    }

    $fullPath = storage_path('app/public/' . $path);
    if (file_exists($fullPath)) {
        return response()->file($fullPath, [
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    try {
        $disk = \Illuminate\Support\Facades\Storage::disk(config('filesystems.default', 'public'));
        if ($disk->exists($path)) {
            $signedUrl = $disk->temporaryUrl($path, now()->addHour());
            return redirect($signedUrl);
        }
    } catch (\Exception $e) {

    }

    abort(404, 'Image not found');
})->where('path', '.*');


/**
 * @unauthenticated
 */
Route::get('/version', function () {
    $version = [
        'app' => config('app.name', 'Koupii API'),
        'version' => '1.0.0',
        'environment' => config('app.env'),
        'timestamp' => now()->toISOString(),
    ];

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

/**
 * Assignment System & Student Dashboard
 */
require __DIR__ . '/../v1/assignment.php';

/**
 * Teacher Dashboard Statistics
 */
require __DIR__ . '/../v1/dashboard.php';

/**
 * Public Plans (for pricing page, no auth required)
 */
Route::get('/plans', [\App\Http\Controllers\V1\Public\PublicPlanController::class, 'index']);
