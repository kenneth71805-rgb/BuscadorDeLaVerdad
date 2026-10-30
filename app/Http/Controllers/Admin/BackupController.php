<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\BackupRunner;
use App\Support\DatabaseDumper;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BackupController extends Controller
{
    public function index()
    {
        $runner = new BackupRunner(app(DatabaseDumper::class));

        return Inertia::render('admin/backups', [
            'backups' => collect($runner->list())->map(fn ($b) => ['name' => $b['name'], 'mb' => round($b['bytes'] / 1048576, 2), 'at' => date('c', $b['at'])])->all(),
            'keep' => (int) config('security.backup_keep'),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $path = (new BackupRunner(app(DatabaseDumper::class)))->create();
        } catch (\Throwable $e) {
            report($e);
            AuditLog::record('backup.failed', null, ['error' => $e->getMessage()], $request->user());

            return back()->withErrors(['backup' => 'The backup failed: '.$e->getMessage()]);
        }

        AuditLog::record('backup.created', null, ['file' => basename($path)], $request->user());

        return back()->with('success', 'Backup created: '.basename($path));
    }
}
