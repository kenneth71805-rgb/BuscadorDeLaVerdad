<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Services\Notifier;
use Illuminate\Console\Command;

class RemindAssignments extends Command
{
    protected $signature = 'assignments:remind';

    protected $description = 'Notify assignees about deadlines due within 24 hours, and about assignments that became overdue (once each).';

    public function handle(): int
    {
        $soon = 0;
        $late = 0;

        Assignment::with('assignee')->whereIn('status', ['open', 'in_progress'])
            ->whereNull('reminded_at')->whereBetween('due_at', [now(), now()->addDay()])->each(function (Assignment $a) use (&$soon) {
                Notifier::send([$a->assignee], "Due soon: “{$a->title}” is due ".$a->due_at->format('M j, g:i A').'.', route('assignments.edit', $a, false), 'deadline');
                $a->forceFill(['reminded_at' => now()])->save();
                $soon++;
            });

        Assignment::with(['assignee', 'creator'])->whereIn('status', ['open', 'in_progress'])
            ->whereNull('overdue_notified_at')->where('due_at', '<', now())->each(function (Assignment $a) use (&$late) {
                Notifier::send([$a->assignee, $a->creator], "Overdue: “{$a->title}” was due ".$a->due_at->format('M j, g:i A').'.', route('assignments.edit', $a, false), 'overdue');
                $a->forceFill(['overdue_notified_at' => now()])->save();
                $late++;
            });

        $this->info("Reminders sent: {$soon} due soon, {$late} overdue.");

        return self::SUCCESS;
    }
}
