<?php

namespace App\Console\Commands;

use App\Models\ReadingSubmission;
use App\Models\ReadingQuestionAnswer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RegradeMatchingAnswers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reading:regrade-matching
                            {--submission= : Re-grade a specific submission ID}
                            {--dry-run : Show what would change without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-grade matching_heading / matching_information items so each item earns its correct share of the parent question points';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $specificId = $this->option('submission');

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be saved.');
        }

        // Load completed ReadingTask submissions (not legacy Test submissions)
        $query = ReadingSubmission::with(['readingTask', 'answers'])
            ->whereNotNull('reading_task_id')
            ->whereIn('status', ['completed', 'submitted']);

        if ($specificId) {
            $query->where('id', $specificId);
        }

        $submissions = $query->get();

        if ($submissions->isEmpty()) {
            $this->info('No matching submissions found.');
            return self::SUCCESS;
        }

        $this->info("Found {$submissions->count()} submission(s) to process.");

        $totalFixed = 0;

        foreach ($submissions as $submission) {
            $task = $submission->readingTask;
            if (!$task || !$task->passages) {
                continue;
            }

            // Build a map: reading_task_question_id => per-item points
            $itemPointsMap = [];
            foreach ($task->passages as $passage) {
                foreach ($passage['question_groups'] ?? [] as $group) {
                    foreach ($group['questions'] ?? [] as $question) {
                        $items = $question['items'] ?? null;
                        $qType = $question['question_type'] ?? '';

                        // Only process matching_* question types that have items
                        if (!str_starts_with($qType, 'matching_') || !is_array($items) || count($items) === 0) {
                            continue;
                        }

                        $parentKey = (string) ($question['id'] ?? $question['question_number'] ?? '');
                        $parentPoints = (float) ($question['points'] ?? $question['points_value'] ?? 1);
                        // Each item earns the full parent points_value when correct

                        foreach ($items as $item) {
                            $iId = $item['id'] ?? null;
                            $iNumber = $item['question_number'] ?? null;

                            // Determine the key used when the answer record was created
                            $itemKey = null;
                            if ($iId !== null) {
                                $itemKey = (string) $iId;
                            } elseif ($parentKey !== '' && $iNumber !== null) {
                                $itemKey = $parentKey . '-item-' . (string) $iNumber;
                            } elseif ($iNumber !== null) {
                                $itemKey = (string) $iNumber;
                            }

                            if ($itemKey !== null) {
                                // Each item earns the full parent points_value
                                $pointsForItem = (float) ($item['points'] ?? $item['points_value'] ?? $parentPoints);
                                $itemPointsMap[$itemKey] = $pointsForItem;
                            }
                        }
                    }
                }
            }

            if (empty($itemPointsMap)) {
                continue;
            }

            // Find answer records for this submission that match the item keys
            $answers = $submission->answers()
                ->whereIn('reading_task_question_id', array_keys($itemPointsMap))
                ->get();

            foreach ($answers as $answer) {
                $key = (string) $answer->reading_task_question_id;
                $correctPoints = $itemPointsMap[$key] ?? null;

                if ($correctPoints === null) {
                    continue;
                }

                $expectedEarned = $answer->is_correct ? $correctPoints : 0;

                // Only update if points_earned is wrong
                if ((float) $answer->points_earned !== $expectedEarned) {
                    $this->line(sprintf(
                        '  [%s] answer %s: points_earned %s → %s (is_correct=%s)',
                        $submission->id,
                        $key,
                        $answer->points_earned,
                        $expectedEarned,
                        $answer->is_correct ? 'true' : 'false'
                    ));

                    if (!$dryRun) {
                        $answer->update(['points_earned' => $expectedEarned]);
                    }

                    $totalFixed++;
                }
            }

            // Recalculate the submission totals after fixing individual answers
            if (!$dryRun && $totalFixed > 0) {
                $submission->calculateScore();
            }
        }

        if ($dryRun) {
            $this->warn("DRY RUN complete. {$totalFixed} answer record(s) would be updated.");
        } else {
            $this->info("Done. {$totalFixed} answer record(s) updated and scores recalculated.");
        }

        return self::SUCCESS;
    }
}
