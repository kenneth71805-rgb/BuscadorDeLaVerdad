<?php

namespace App\Http\Controllers\Newsroom;

use App\Http\Controllers\Controller;
use App\Http\Requests\FaqRequest;
use App\Models\AuditLog;
use App\Models\FaqEntry;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FaqController extends Controller
{
    public function index()
    {
        return Inertia::render('newsroom/faq/index', [
            'entries' => FaqEntry::orderBy('sort_order')->orderBy('id')->get(['id', 'question', 'is_active', 'sort_order']),
            'misses' => DB::table('assistant_misses')->latest('created_at')->limit(30)->get(['question', 'created_at'])
                ->map(fn ($m) => ['question' => $m->question, 'at' => $m->created_at]),
        ]);
    }

    public function create()
    {
        return Inertia::render('newsroom/faq/create');
    }

    public function store(FaqRequest $request)
    {
        $entry = new FaqEntry($request->safe()->only(['question', 'answer', 'keywords', 'sort_order']));
        $entry->is_active = $request->boolean('is_active');
        $entry->save();
        AuditLog::record('faq.created', $entry, [], $request->user());

        return redirect()->route('newsroom.faq.index')->with('success', 'FAQ entry saved.');
    }

    public function edit(FaqEntry $faq)
    {
        return Inertia::render('newsroom/faq/edit', ['entry' => $faq->only(['id', 'question', 'answer', 'keywords', 'sort_order', 'is_active'])]);
    }

    public function update(FaqRequest $request, FaqEntry $faq)
    {
        $faq->fill($request->safe()->only(['question', 'answer', 'keywords', 'sort_order']));
        $faq->is_active = $request->boolean('is_active');
        $faq->save();
        AuditLog::record('faq.updated', $faq, [], $request->user());

        return redirect()->route('newsroom.faq.index')->with('success', 'FAQ entry saved.');
    }
}
