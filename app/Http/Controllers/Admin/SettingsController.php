<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function edit()
    {
        return Inertia::render('admin/settings', ['settings' => Setting::allValues()]);
    }

    public function update(Request $request)
    {
        $values = $request->validate(Setting::RULES);
        Setting::put($values);
        AuditLog::record('settings.updated', null, ['keys' => array_keys($values)], $request->user());

        return back()->with('success', 'Settings saved.');
    }
}
