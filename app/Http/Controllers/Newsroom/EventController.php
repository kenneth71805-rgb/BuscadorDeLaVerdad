<?php

namespace App\Http\Controllers\Newsroom;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Models\AuditLog;
use App\Models\CalendarEvent;
use App\Models\Category;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index()
    {
        return Inertia::render('newsroom/events/index', [
            'events' => CalendarEvent::latest('starts_at')->limit(100)->get()->map(fn (CalendarEvent $e) => [
                'id' => $e->id, 'title' => $e->title, 'starts_at' => $e->starts_at->toIso8601String(), 'location' => $e->location, 'is_public' => $e->is_public,
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('newsroom/events/create', ['categories' => $this->categories()]);
    }

    public function store(EventRequest $request)
    {
        $event = new CalendarEvent($request->safe()->only(['title', 'description', 'starts_at', 'ends_at', 'location', 'category_id']));
        $event->is_public = $request->boolean('is_public');
        $event->created_by = $request->user()->id;
        $event->save();
        AuditLog::record('event.created', $event, ['public' => $event->is_public], $request->user());

        return redirect()->route('newsroom.events.index')->with('success', 'Event saved.');
    }

    public function edit(CalendarEvent $event)
    {
        return Inertia::render('newsroom/events/edit', [
            'event' => $event->only(['id', 'title', 'description', 'location', 'category_id', 'is_public']) + [
                'starts_at' => $event->starts_at->format('Y-m-d\TH:i'),
                'ends_at' => $event->ends_at?->format('Y-m-d\TH:i'),
            ],
            'categories' => $this->categories(),
        ]);
    }

    public function update(EventRequest $request, CalendarEvent $event)
    {
        $event->fill($request->safe()->only(['title', 'description', 'starts_at', 'ends_at', 'location', 'category_id']));
        $event->is_public = $request->boolean('is_public');
        $event->save();
        AuditLog::record('event.updated', $event, ['public' => $event->is_public], $request->user());

        return redirect()->route('newsroom.events.index')->with('success', 'Event saved.');
    }

    private function categories(): array
    {
        return Category::orderBy('sort_order')->get(['id', 'name'])->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->all();
    }
}
