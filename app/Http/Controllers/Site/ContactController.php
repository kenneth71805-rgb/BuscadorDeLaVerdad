<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use App\Models\Setting;
use App\Support\PublicData;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function show()
    {
        $s = Setting::allValues();

        return Inertia::render('site/contact', [
            'meta' => Seo::page('Contact', 'How to reach the editors of Los Sueños.', '/contact'),
            'nav' => PublicData::nav(),
            'info' => array_intersect_key($s, array_flip(['contact_email', 'contact_address', 'facebook_url', 'instagram_url'])),
            'types' => collect(ContactSubmission::TYPES)->except('support')->all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'type' => ['required', Rule::in(array_keys(collect(ContactSubmission::TYPES)->except('support')->all()))],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'string'], // honeypot: real visitors never see or fill this
        ]);

        if (empty($data['website'])) {
            ContactSubmission::create([
                'name' => $data['name'], 'email' => $data['email'], 'type' => $data['type'], 'message' => $data['message'],
                'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            ]);
        }

        // Bots get the same answer as people, so they learn nothing.
        return back()->with('success', 'Thank you. Your message has been received.');
    }
}
