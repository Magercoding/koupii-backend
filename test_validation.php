<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Http\Requests\V1\SpeakingTask\StartSpeakingSubmissionRequest;
use Illuminate\Support\Facades\Validator;

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$data = [
    'task_id' => '00000000-0000-0000-0000-000000000000',
    'assignment_id' => 'invalid-uuid',
];

$request = new StartSpeakingSubmissionRequest();
$rules = $request->rules();

$validator = Validator::make($data, $rules);

echo "Rules: " . json_encode($rules, JSON_PRETTY_PRINT) . "\n";
echo "Fails: " . ($validator->fails() ? "Yes" : "No") . "\n";
echo "Errors: " . json_encode($validator->errors()->toArray(), JSON_PRETTY_PRINT) . "\n";
