<?php
use App\Models\WritingTask;
use App\Http\Resources\V1\WritingTask\WritingTaskResource;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$task = WritingTask::with(['questions.resources'])->first();
if (!$task) {
    echo "No writing task found\n";
    exit;
}

$resource = new WritingTaskResource($task);
echo json_encode($resource->resolve(), JSON_PRETTY_PRINT);
