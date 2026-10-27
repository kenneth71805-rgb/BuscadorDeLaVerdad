<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Article;
use App\Models\Assignment;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->query('month'))
            ? Carbon::createFromFormat('!Y-m', $request->query('month'))
            : now()->startOfMonth();
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $items = collect();

        CalendarEvent::with('category:id,name')->whereBetween('starts_at', [$start, $end])->get()->each(fn (CalendarEvent $e) => $items->push([
            'at' => $e->starts_at, 'type' => 'event', 'title' => $e->title,
            'detail' => trim(($e->location ? $e->location.' · ' : '').($e->category?->name ?? '').($e->is_public ? ' · public' : ''), ' ·'),
            'url' => $user->hasRole(Role::Admin, Role::EditorInChief) ? route('newsroom.events.edit', $e, false) : null,
        ]));

        Assignment::query()->visibleTo($user)->with(['category:id,name', 'assignee:id,name'])
            ->where('status', '!=', 'cancelled')->whereBetween('due_at', [$start, $end])->get()->each(fn (Assignment $a) => $items->push([
                'at' => $a->due_at, 'type' => 'deadline', 'title' => $a->title,
                'detail' => "{$a->category->name} · {$a->assignee->name} · ".Assignment::STATUSES[$a->status].($a->isOverdue() ? ' · OVERDUE' : ''),
                'url' => route('assignments.edit', $a, false),
            ]));

        Article::query()->visibleTo($user)->with('category:id,name')->where('status', 'scheduled')
            ->whereBetween('scheduled_for', [$start, $end])->get()->each(fn (Article $a) => $items->push([
                'at' => $a->scheduled_for, 'type' => 'publication', 'title' => $a->title,
                'detail' => $a->category->name.' · scheduled to publish', 'url' => route('articles.show', $a, false),
            ]));

        $days = $items->sortBy('at')->groupBy(fn ($i) => $i['at']->format('Y-m-d'))->map(fn ($group, $date) => [
            'date' => $date,
            'label' => Carbon::parse($date)->format('l, F j'),
            'items' => $group->map(fn ($i) => [
                'time' => $i['at']->format('g:i A'), 'type' => $i['type'], 'title' => $i['title'], 'detail' => $i['detail'], 'url' => $i['url'],
            ])->values(),
        ])->values();

        return Inertia::render('calendar/index', [
            'month' => $month->format('Y-m'),
            'label' => $month->format('F Y'),
            'prev' => $month->copy()->subMonth()->format('Y-m'),
            'next' => $month->copy()->addMonth()->format('Y-m'),
            'days' => $days,
        ]);
    }
}
