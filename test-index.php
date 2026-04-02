<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('role', 'student')->first();
$teacher = App\Models\User::where('role', 'teacher')->first();

echo "As Student\n";
Auth::login($user);

// Calling SpeakingTaskController@index
$request = Illuminate\Http\Request::create('/api/v1/speaking/tasks', 'GET');
$request->setUserResolver(function () use ($user) { return $user; });
$controller = new App\Http\Controllers\V1\SpeakingTask\SpeakingTaskController(new App\Services\V1\SpeakingTask\SpeakingTaskService());
$response = $controller->index($request);
echo "Speaking Res: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() == 500) { echo $response->getContent() . "\n"; }

$request = Illuminate\Http\Request::create('/api/v1/listening/tasks', 'GET');
$request->setUserResolver(function () use ($user) { return $user; });
$controller = new App\Http\Controllers\V1\Listening\ListeningTaskController();
$response = $controller->index($request);
echo "Listening Res: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() == 500) { echo $response->getContent() . "\n"; }

echo "As Teacher\n";
Auth::login($teacher);

$request = Illuminate\Http\Request::create('/api/v1/speaking/tasks', 'GET');
$request->setUserResolver(function () use ($teacher) { return $teacher; });
$controller = new App\Http\Controllers\V1\SpeakingTask\SpeakingTaskController(new App\Services\V1\SpeakingTask\SpeakingTaskService());
$response = $controller->index($request);
echo "Speaking Res: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() == 500) { echo $response->getContent() . "\n"; }

$request = Illuminate\Http\Request::create('/api/v1/listening/tasks', 'GET');
$request->setUserResolver(function () use ($teacher) { return $teacher; });
$controller = new App\Http\Controllers\V1\Listening\ListeningTaskController();
$response = $controller->index($request);
echo "Listening Res: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() == 500) { echo $response->getContent() . "\n"; }

