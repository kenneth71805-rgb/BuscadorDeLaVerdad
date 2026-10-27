<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

/** Every query goes through the signed-in user's own notifications, so nobody can read or change another person's. */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('notifications/index', [
            'items' => $request->user()->notifications()->paginate(20)->through(fn ($n) => [
                'id' => $n->id, 'message' => $n->data['message'] ?? '', 'url' => $n->data['url'] ?? null,
                'kind' => $n->data['kind'] ?? '', 'read' => $n->read_at !== null, 'at' => $n->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function read(Request $request, string $id)
    {
        $request->user()->notifications()->whereKey($id)->first()?->markAsRead();

        return back();
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
