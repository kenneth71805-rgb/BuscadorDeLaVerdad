<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use App\Models\Setting;
use App\Services\AssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssistantController extends Controller
{
    public function ask(Request $request, AssistantService $assistant)
    {
        abort_unless($this->enabled(), 404);
        $data = $request->validate(['question' => ['required', 'string', 'max:200']]);

        $reply = $assistant->answer($data['question']);

        if (! $reply) {
            DB::table('assistant_misses')->insert(['question' => AssistantService::scrub($data['question']), 'created_at' => now()]);
        }

        return response()->json($reply ?? ['answer' => null, 'links' => [], 'source' => 'none']);
    }

    /** Hand the question to the staff-managed support queue (the contact inbox). */
    public function escalate(Request $request)
    {
        abort_unless($this->enabled(), 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'message' => ['required', 'string', 'min:5', 'max:2000'],
            'consent' => ['accepted'],
        ]);

        ContactSubmission::create([
            'name' => $data['name'], 'email' => $data['email'], 'type' => 'support', 'message' => $data['message'],
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
        ]);

        return response()->json(['ok' => true]);
    }

    private function enabled(): bool
    {
        return Setting::allValues()['assistant_enabled'] !== '0';
    }
}
