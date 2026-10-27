<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Support\PublicData;
use Inertia\Inertia;

class EventController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('site/events', [
            'nav' => PublicData::nav(),
            'events' => CalendarEvent::with('category:id,name')->where('is_public', true)
                ->where('starts_at', '>=', now()->startOfDay())->orderBy('starts_at')->paginate(10)
                ->through(fn (CalendarEvent $e) => self::card($e)),
        ]);
    }

    /** Public events expose only what is meant to be public. */
    public static function card(CalendarEvent $e): array
    {
        return [
            'title' => $e->title, 'description' => $e->description, 'location' => $e->location,
            'starts_at' => $e->starts_at->toIso8601String(), 'ends_at' => $e->ends_at?->toIso8601String(),
            'category' => $e->category?->name,
        ];
    }
}
