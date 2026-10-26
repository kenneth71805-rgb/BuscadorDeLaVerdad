<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** Contact inquiries contain personal data: Admin and Editor-in-Chief only (enforced by route middleware). */
class InboxController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        return Inertia::render('inbox/index', [
            'items' => ContactSubmission::query()
                ->when(in_array($status, ['new', 'read', 'resolved'], true), fn ($q) => $q->where('status', $status))
                ->latest()->paginate(20)->withQueryString()
                ->through(fn (ContactSubmission $c) => [
                    'id' => $c->id, 'name' => $c->name, 'type' => ContactSubmission::TYPES[$c->type] ?? $c->type,
                    'status' => $c->status, 'created_at' => $c->created_at?->toIso8601String(),
                ]),
            'status' => $status,
        ]);
    }

    public function show(ContactSubmission $submission)
    {
        return Inertia::render('inbox/show', [
            'item' => [
                'id' => $submission->id, 'name' => $submission->name, 'email' => $submission->email,
                'type' => ContactSubmission::TYPES[$submission->type] ?? $submission->type,
                'message' => $submission->message, 'status' => $submission->status,
                'created_at' => $submission->created_at?->toIso8601String(),
                'closed_at' => $submission->closed_at?->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, ContactSubmission $submission)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'read', 'resolved'])]]);

        $submission->status = $data['status'];
        $submission->closed_at = $data['status'] === 'resolved' ? ($submission->closed_at ?? now()) : null;
        $submission->save();
        AuditLog::record('contact.status_changed', $submission, ['status' => $data['status']], $request->user());

        return back()->with('success', 'Status updated.');
    }
}
