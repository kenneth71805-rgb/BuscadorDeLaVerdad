<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\StaffProfile;
use App\Support\PublicData;
use Inertia\Inertia;

class AboutController extends Controller
{
    public function __invoke()
    {
        $profiles = StaffProfile::publicOnly()->with('category:id,name')
            ->orderBy('sort_order')->orderBy('name')->get()
            ->map(fn (StaffProfile $p) => [
                // Only fields meant for the public. No email, phone or account data exists on this table.
                'id' => $p->id, 'name' => $p->name, 'position' => $p->position,
                'category' => $p->category?->name, 'bio' => $p->bio, 'link_url' => $p->link_url,
                'archived' => $p->archived_at !== null,
            ]);

        return Inertia::render('site/about', [
            'nav' => PublicData::nav(),
            'content' => Setting::allValues(),
            'current' => $profiles->where('archived', false)->values(),
            'former' => $profiles->where('archived', true)->values(),
        ]);
    }
}
