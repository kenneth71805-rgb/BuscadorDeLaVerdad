<?php

use Illuminate\Foundation\Inspiring;
use App\Services\ArticleWorkflow;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Publishes articles whose scheduled time has arrived. Needs `php artisan schedule:work` (dev) or a cron entry (production).
Schedule::call(fn () => app(ArticleWorkflow::class)->publishDueScheduled())->everyMinute()->name('publish-due-articles')->withoutOverlapping();

// Retention: resolved contact inquiries are deleted one week after closure.
Schedule::command('contacts:purge')->daily()->name('purge-contacts')->withoutOverlapping();

// Deadline reminders and overdue notices (each sent once per assignment).
Schedule::command('assignments:remind')->hourly()->name('remind-assignments')->withoutOverlapping();
