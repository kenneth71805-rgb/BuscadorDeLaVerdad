<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Services\BackupRunner;
use App\Support\DatabaseDumper;
use Illuminate\Console\Command;

class RunBackup extends Command
{
    protected $signature = 'backup:run';

    protected $description = 'Back up the database and all uploaded photos and PDFs into one archive, and remove old archives.';

    public function handle(): int
    {
        try {
            $path = (new BackupRunner(app(DatabaseDumper::class)))->create();
        } catch (\Throwable $e) {
            report($e);
            AuditLog::record('backup.failed', null, ['error' => $e->getMessage()]);
            $this->error('Backup failed: '.$e->getMessage());

            return self::FAILURE;
        }

        AuditLog::record('backup.created', null, ['file' => basename($path)]);
        $this->info('Backup created: '.$path);

        return self::SUCCESS;
    }
}
