<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\StaffProfile;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/profiles/index', [
            'profiles' => StaffProfile::with('category:id,name')->orderBy('sort_order')->orderBy('name')->get()
                ->map(fn (StaffProfile $p) => [
                    'id' => $p->id, 'name' => $p->name, 'position' => $p->position,
                    'category' => $p->category?->name, 'is_public' => $p->is_public,
                    'archived' => $p->archived_at !== null, 'sort_order' => $p->sort_order,
                ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/profiles/create', ['categories' => $this->categories()]);
    }

    public function store(ProfileRequest $request)
    {
        $profile = new StaffProfile($request->safe()->only(['name', 'position', 'category_id', 'bio', 'link_url', 'sort_order']));
        $this->applyFlags($profile, $request->validated());
        $profile->save();
        AuditLog::record('profile.created', $profile, ['public' => $profile->is_public], $request->user());

        return redirect()->route('admin.profiles.index')->with('success', 'Profile created.');
    }

    public function edit(StaffProfile $profile)
    {
        return Inertia::render('admin/profiles/edit', [
            'profile' => $profile->only(['id', 'name', 'position', 'category_id', 'bio', 'link_url', 'sort_order', 'is_public']) + ['archived' => $profile->archived_at !== null],
            'categories' => $this->categories(),
        ]);
    }

    public function update(ProfileRequest $request, StaffProfile $profile)
    {
        $profile->fill($request->safe()->only(['name', 'position', 'category_id', 'bio', 'link_url', 'sort_order']));
        $this->applyFlags($profile, $request->validated());
        $profile->save();
        AuditLog::record('profile.updated', $profile, ['public' => $profile->is_public, 'archived' => $profile->archived_at !== null], $request->user());

        return redirect()->route('admin.profiles.index')->with('success', 'Profile saved.');
    }

    private function applyFlags(StaffProfile $profile, array $data): void
    {
        $profile->is_public = (bool) $data['is_public'];
        $profile->archived_at = $data['archived'] ? ($profile->archived_at ?? now()) : null;
    }

    private function categories(): array
    {
        return Category::orderBy('sort_order')->get(['id', 'name'])->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->all();
    }
}
