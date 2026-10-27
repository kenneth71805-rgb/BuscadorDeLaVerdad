<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $action = trim((string) $request->query('action', ''));
        $actor = $request->integer('actor') ?: null;

        $logs = AuditLog::with('actor:id,name')
            ->when($action !== '', fn ($q) => $q->where('action', 'like', addcslashes($action, '%_\\').'%'))
            ->when($actor, fn ($q) => $q->where('actor_id', $actor))
            ->when($request->query('from'), fn ($q, $d) => $q->where('created_at', '>=', $this->day($d)?->startOfDay() ?? '1970-01-01'))
            ->when($request->query('to'), fn ($q, $d) => $q->where('created_at', '<=', $this->day($d)?->endOfDay() ?? now()))
            ->latest('id')->paginate(30)->withQueryString()
            ->through(fn (AuditLog $l) => [
                'id' => $l->id, 'action' => $l->action, 'actor' => $l->actor?->name ?? 'System',
                'subject' => $l->subject_type ? class_basename($l->subject_type).' #'.$l->subject_id : null,
                'meta' => $l->meta, 'at' => $l->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/audit', [
            'logs' => $logs,
            'filters' => ['action' => $action, 'actor' => $actor, 'from' => $request->query('from'), 'to' => $request->query('to')],
            'staff' => User::whereNotNull('role')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function day(mixed $value): ?\Illuminate\Support\Carbon
    {
        try {
            return \Illuminate\Support\Carbon::createFromFormat('!Y-m-d', (string) $value) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
