<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidStatusTransition;
use App\Http\Requests\CorrectionRequest;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Services\CorrectionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CorrectionController extends Controller
{
    use AuthorizesRequests;

    public function create(Article $article)
    {
        $this->authorize('correct', $article);

        if ($pending = $article->revisions()->where('status', 'pending')->first()) {
            return redirect()->route('corrections.show', $pending);
        }

        return Inertia::render('articles/correction', ['article' => $article->only(['id', 'title', 'subtitle', 'body'])]);
    }

    public function store(CorrectionRequest $request, Article $article, CorrectionService $corrections)
    {
        try {
            $revision = $corrections->propose($article, $request->user(), $request->validated());
        } catch (InvalidStatusTransition $e) {
            return back()->withErrors(['correction' => $e->getMessage()]);
        }

        return redirect()->route('articles.show', $article)->with('success',
            $revision->status === 'applied' ? 'Correction published.' : 'Correction sent to the Editor-in-Chief for review. The live article is unchanged until it is approved.');
    }

    public function show(Request $request, ArticleRevision $revision)
    {
        $this->authorize('view', $revision);
        $revision->load(['article.category:id,name', 'requester:id,name', 'decider:id,name']);
        $article = $revision->article;
        $applied = $revision->status === 'applied';

        return Inertia::render('corrections/show', [
            'revision' => [
                'id' => $revision->id, 'version' => $revision->version, 'status' => $revision->status,
                'reason' => $revision->reason, 'public_note' => $revision->public_note,
                'requested_by' => $revision->requester->name, 'created_at' => $revision->created_at?->toIso8601String(),
                'decided_by' => $revision->decider?->name, 'decided_at' => $revision->decided_at?->toIso8601String(), 'decision_reason' => $revision->decision_reason,
                'proposed' => ['title' => $revision->title, 'subtitle' => $revision->subtitle, 'body' => $revision->body],
                // While pending, "current" is the live text; once applied it is the text that was replaced.
                'current' => $applied
                    ? ['title' => $revision->previous_title, 'subtitle' => $revision->previous_subtitle, 'body' => $revision->previous_body]
                    : ['title' => $article->title, 'subtitle' => $article->subtitle, 'body' => $article->body],
            ],
            'article' => ['id' => $article->id, 'title' => $article->title, 'category' => $article->category->name],
            'can_decide' => $request->user()->can('decide', $revision),
        ]);
    }

    public function decide(Request $request, ArticleRevision $revision, CorrectionService $corrections)
    {
        $this->authorize('decide', $revision);
        $data = $request->validate(['decision' => ['required', 'in:approve,decline'], 'reason' => ['nullable', 'string', 'max:1000']]);

        try {
            $corrections->decide($revision, $request->user(), $data['decision'] === 'approve', $data['reason'] ?? null);
        } catch (InvalidStatusTransition $e) {
            return back()->withErrors(['correction' => $e->getMessage()]);
        }

        return redirect()->route('articles.show', $revision->article_id)->with('success', $data['decision'] === 'approve' ? 'Correction published.' : 'Correction declined.');
    }
}
