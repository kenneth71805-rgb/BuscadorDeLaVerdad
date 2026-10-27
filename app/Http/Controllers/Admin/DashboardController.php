<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ArticleStats;
use App\Services\Deadlines;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(\Illuminate\Http\Request $request)
    {
        $byStatus = ArticleStats::byStatus();

        return Inertia::render('admin/dashboard', [
            'deadlines' => Deadlines::upcoming($request->user()),
            'stats' => [
                'published' => $byStatus['published'],
                'pending' => ArticleStats::pending($byStatus),
                'scheduled' => $byStatus['scheduled'],
                'staff' => User::whereNotNull('role')->where('is_active', true)->count(),
            ],
            'recentActivity' => AuditLog::with('actor:id,name')->latest('id')->limit(10)->get()
                ->map(fn (AuditLog $log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'actor' => $log->actor?->name ?? 'System',
                    'at' => $log->created_at?->toIso8601String(),
                ]),
        ]);
    }
}
