<?php

namespace App\Services\V1\Test;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class DualAttemptService
{
    public const OFFICIAL_ATTEMPT = 1;

    public const PRACTICE_ATTEMPT = 2;

    /**
     * First completed submission is frozen for teachers; all further tries reuse attempt #2.
     */
    public static function resolveAttemptNumber(Builder $baseQuery, array $completedStatuses): int
    {
        $first = (clone $baseQuery)
            ->where('attempt_number', self::OFFICIAL_ATTEMPT)
            ->first();

        if (!$first || !in_array($first->status, $completedStatuses, true)) {
            return self::OFFICIAL_ATTEMPT;
        }

        return self::PRACTICE_ATTEMPT;
    }

    public static function shouldResetPracticeAttempt(
        Model $submission,
        int $attemptNumber,
        array $completedStatuses,
    ): bool {
        return $attemptNumber === self::PRACTICE_ATTEMPT
            && in_array($submission->status, $completedStatuses, true);
    }

    /**
     * Teacher dashboards/reports only use each student's official (first) attempt.
     */
    public static function filterForTeacherView(Collection $submissions): Collection
    {
        return $submissions
            ->groupBy('student_id')
            ->map(function (Collection $group) {
                return $group->firstWhere('attempt_number', self::OFFICIAL_ATTEMPT)
                    ?? $group->sortBy('attempt_number')->first();
            })
            ->filter()
            ->values();
    }

    /**
     * Students see their practice attempt when available, otherwise the official one.
     */
    public static function getStudentDisplaySubmission(
        Builder $baseQuery,
        array $completedStatuses,
        string $inProgressStatus = 'in_progress',
    ): ?Model {
        $practice = (clone $baseQuery)
            ->where('attempt_number', self::PRACTICE_ATTEMPT)
            ->first();

        if ($practice) {
            if (
                in_array($practice->status, $completedStatuses, true)
                || $practice->status === $inProgressStatus
            ) {
                return $practice;
            }
        }

        return (clone $baseQuery)
            ->where('attempt_number', self::OFFICIAL_ATTEMPT)
            ->first()
            ?? (clone $baseQuery)->orderBy('attempt_number')->first();
    }
}
