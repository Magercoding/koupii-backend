<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\StudentAssignment;
use App\Notifications\DeadlineReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendDeadlineReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-deadline-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications to students for assignments due in 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting deadline reminder scan...');

        // Find assignments due in the next 24 hours
        $now = Carbon::now();
        $tomorrow = $now->copy()->addDay();

        $upcomingAssignments = Assignment::where('due_date', '>', $now)
            ->where('due_date', '<=', $tomorrow)
            ->where('is_published', true)
            ->where('status', 'active')
            ->get();

        $this->info('Found ' . $upcomingAssignments->count() . ' upcoming assignments.');

        foreach ($upcomingAssignments as $assignment) {
            // Find students who haven't completed this assignment yet
            $pendingStudents = StudentAssignment::where('assignment_id', $assignment->id)
                ->whereIn('status', [StudentAssignment::STATUS_NOT_STARTED, StudentAssignment::STATUS_IN_PROGRESS])
                ->with('student')
                ->get()
                ->pluck('student')
                ->filter();

            if ($pendingStudents->isNotEmpty()) {
                $this->info('Notifying ' . $pendingStudents->count() . ' students for: ' . $assignment->getAssignmentTitle());
                Notification::send($pendingStudents, new DeadlineReminderNotification($assignment));
            }
        }

        $this->info('Deadline reminder scan completed.');
    }
}
