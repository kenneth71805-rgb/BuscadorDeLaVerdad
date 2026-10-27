<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\ContactSubmission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeContacts extends Command
{
    protected $signature = 'contacts:purge';

    protected $description = 'Delete contact inquiries one week after they were resolved (data retention policy).';

    public function handle(): int
    {
        $count = ContactSubmission::whereNotNull('closed_at')->where('closed_at', '<=', now()->subWeek())->delete();

        // Unanswered helper questions are kept for one week, too.
        $misses = DB::table('assistant_misses')->where('created_at', '<=', now()->subWeek())->delete();

        if ($count > 0) {
            AuditLog::record('contact.purged', null, ['count' => $count]);
        }
        $this->info("Purged {$count} resolved inquiries and {$misses} old helper questions.");

        return self::SUCCESS;
    }
}
