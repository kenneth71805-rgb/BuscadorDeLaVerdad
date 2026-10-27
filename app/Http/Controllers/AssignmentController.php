<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\AssignmentRequest;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\User;
use App\Services\Notifier;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AssignmentController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status');
        $mine = $request->boolean('mine');

        $items = Assignment::query()->visibleTo($user)->with(['category:id,name', 'assignee:id,name'])
            ->when($mine, fn ($q) => $q->where('assigned_to', $user->id))
            ->when(array_key_exists((string) $status, Assignment::STATUSES), fn ($q) => $q->where('status', $status))
            ->orderByRaw("case when status in ('open','in_progress') then 0 else 1 end")->orderBy('due_at')
            ->paginate(20)->withQueryString()
            ->through(fn (Assignment $a) => [
                'id' => $a->id, 'title' => $a->title, 'category' => $a->category->name, 'assignee' => $a->assignee->name,
                'due_at' => $a->due_at->toIso8601String(), 'status' => $a->status, 'overdue' => $a->isOverdue(),
                'can_edit' => $user->can('update', $a), 'mine' => $a->assigned_to === $user->id,
            ]);

        return Inertia::render('assignments/index', ['items' => $items, 'filters' => ['status' => $status, 'mine' => $mine], 'statuses' => Assignment::STATUSES]);
    }

    public function create(Request $request)
    {
        return Inertia::render('assignments/create', $this->options($request));
    }

    public function store(AssignmentRequest $request)
    {
        $data = $request->validated();
        $a = new Assignment($data);
        $a->created_by = $request->user()->id;
        $a->save();

        Notifier::send([$a->assignee], "New assignment: “{$a->title}”, due ".$a->due_at->format('M j, g:i A').'.', route('assignments.edit', $a, false), 'assignment', $request->user());
        AuditLog::record('assignment.created', $a, ['assignee' => $a->assigned_to], $request->user());

        return redirect()->route('assignments.index')->with('success', 'Assignment created.');
    }

    public function edit(Request $request, Assignment $assignment)
    {
        $this->authorize('view', $assignment);

        return Inertia::render('assignments/edit', $this->options($request) + [
            'assignment' => $assignment->only(['id', 'title', 'description', 'category_id', 'assigned_to', 'status']) + ['due_at' => $assignment->due_at->format('Y-m-d\TH:i')],
            'can_edit' => $request->user()->can('update', $assignment),
            'statuses' => Assignment::STATUSES,
        ]);
    }

    public function update(AssignmentRequest $request, Assignment $assignment)
    {
        $this->authorize('update', $assignment);

        $previous = $assignment->assigned_to;
        $assignment->update($request->validated());

        if ($assignment->assigned_to !== $previous) {
            Notifier::send([$assignment->assignee], "You were assigned: “{$assignment->title}”, due ".$assignment->due_at->format('M j, g:i A').'.', route('assignments.edit', $assignment, false), 'assignment', $request->user());
        }
        AuditLog::record('assignment.updated', $assignment, [], $request->user());

        return redirect()->route('assignments.index')->with('success', 'Assignment saved.');
    }

    public function status(Request $request, Assignment $assignment)
    {
        $this->authorize('updateStatus', $assignment);
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(Assignment::STATUSES))]]);

        $assignment->status = $data['status'];
        $assignment->save();

        if ($data['status'] === 'done') {
            Notifier::send([$assignment->creator], "Completed: “{$assignment->title}” was marked done by {$request->user()->name}.", route('assignments.edit', $assignment, false), 'assignment', $request->user());
        }
        AuditLog::record('assignment.status_changed', $assignment, ['status' => $data['status']], $request->user());

        return back()->with('success', 'Status updated.');
    }

    private function options(Request $request): array
    {
        $user = $request->user();
        $categories = $user->hasRole(Role::CategoryHead)
            ? $user->categories()->orderBy('sort_order')->get(['categories.id', 'categories.name'])
            : Category::where('is_active', true)->orderBy('sort_order')->get(['id', 'name']);

        return [
            'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
            'staff' => User::whereNotNull('role')->where('is_active', true)->orderBy('name')->get()
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'role' => $u->role->label()]),
        ];
    }
}
