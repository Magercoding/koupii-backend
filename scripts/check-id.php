<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = $argv[1] ?? '9e7fea1d-de5a-4ef4-980d-812dd89da310';

$models = [
    'Assignment' => \App\Models\Assignment::class,
    'SpeakingTask' => \App\Models\SpeakingTask::class,
    'ReadingTask' => \App\Models\ReadingTask::class,
    'ListeningTask' => \App\Models\ListeningTask::class,
];

foreach ($models as $name => $class) {
    $row = $class::find($id);
    if ($row) {
        echo "$name: " . json_encode($row->only(['id', 'title', 'task_id', 'task_type', 'type'])) . PHP_EOL;
    }
}

$assignments = \App\Models\Assignment::where('task_id', $id)->get(['id', 'title', 'task_id', 'task_type', 'type']);
echo "Assignments with task_id=$id: " . $assignments->toJson() . PHP_EOL;
