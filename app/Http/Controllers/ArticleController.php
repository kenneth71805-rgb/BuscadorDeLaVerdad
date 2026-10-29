<?php

namespace App\Http\Controllers;

use App\Enums\ArticleStatus as S;
use App\Enums\Role;
use App\Exceptions\InvalidStatusTransition;
use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Models\ArticleStatusHistory;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Media;
use App\Services\ArticleWorkflow;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ArticleController extends Controller
{
    use AuthorizesRequests;

    private const LABELS = [
        'draft' => 'Back to draft',
        'submitted' => 'Submit for review',
        'category_approved' => 'Approve for the editor',
        'in_review' => 'Start editorial review',
        'revision_requested' => 'Return for revision',
        'resubmitted' => 'Resubmit',
        'approved' => 'Approve',
        'scheduled' => 'Schedule',
        'published' => 'Publish now',
        'rejected' => 'Reject',
        'archived' => 'Archive',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));

        $articles = Article::query()->visibleTo($user)->with(['category:id,name', 'author:id,name'])
            ->when(S::tryFrom((string) $status), fn ($query, S $s) => $query->where('status', $s->value))
            ->when($q !== '', fn ($query) => $query->where('title', 'like', '%'.addcslashes($q, '%_\\').'%'))
            ->latest('updated_at')->paginate(20)->withQueryString()
            ->through(fn (Article $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'category' => $a->category->name,
                'author' => $a->author->name,
                'status' => $a->status->value,
                'updated_at' => $a->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('articles/index', [
            'articles' => $articles,
            'filters' => ['status' => $status, 'q' => $q],
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Article::class);

        return Inertia::render('articles/create', ['categories' => $this->writableCategories($request), 'media' => $this->pickableMedia($request)]);
    }

    public function store(ArticleRequest $request)
    {
        $this->authorize('create', Article::class);

        $article = DB::transaction(function () use ($request) {
            $article = new Article($request->safe()->only(['category_id', 'title', 'subtitle', 'body', 'lead_media_id']));
            $article->author_id = $request->user()->id;
            $article->slug = $this->uniqueSlug($article->title);
            $article->status = S::Draft;
            $article->save();

            ArticleStatusHistory::create([
                'article_id' => $article->id, 'from_status' => null, 'to_status' => S::Draft->value,
                'actor_id' => $request->user()->id,
            ]);
            AuditLog::record('article.created', $article, [], $request->user());

            return $article;
        });

        return redirect()->route('articles.show', $article)->with('success', 'Draft saved.');
    }

    public function show(Request $request, Article $article)
    {
        $this->authorize('view', $article);
        $user = $request->user();
        $article->load(['category:id,name,slug', 'author:id,name', 'leadMedia', 'statusHistory.actor:id,name']);

        return Inertia::render('articles/show', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'subtitle' => $article->subtitle,
                'paragraphs' => array_values(array_filter(preg_split('/\R{2,}/', trim($article->body)))),
                'category' => $article->category->name,
                'author' => $article->author->name,
                'status' => $article->status->value,
                'scheduled_for' => $article->scheduled_for?->toIso8601String(),
                'published_at' => $article->published_at?->toIso8601String(),
                'public_url' => $article->status === S::Published
                    ? route('site.article', [$article->category->slug, $article->slug], false) : null,
            ],
            'lead' => $article->leadMedia ? [
                'id' => $article->leadMedia->id, 'url' => $article->leadMedia->url('large'), 'alt' => $article->leadMedia->alt,
                'caption' => $article->leadMedia->caption, 'credit' => $article->leadMedia->credit,
                'is_public' => $article->leadMedia->is_public, 'can_release' => $user->can('release', $article->leadMedia),
            ] : null,
            'can_edit' => $user->can('update', $article),
            'can_correct' => $user->can('correct', $article),
            'pending_correction' => ($pending = $article->revisions()->where('status', 'pending')->first()) ? ['id' => $pending->id] : null,
            'corrections' => $article->revisions()->where('status', '!=', 'pending')->get()->map(fn ($r) => [
                'id' => $r->id, 'version' => $r->version, 'status' => $r->status, 'at' => $r->decided_at?->toIso8601String(),
            ]),
            'actions' => $this->actionsFor($request, $article),
            'history' => $article->statusHistory->map(fn (ArticleStatusHistory $h) => [
                'id' => $h->id,
                'from' => $h->from_status?->value,
                'to' => $h->to_status->value,
                'actor' => $h->actor?->name ?? 'System',
                'reason' => $h->reason,
                'at' => $h->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function edit(Request $request, Article $article)
    {
        $this->authorize('update', $article);

        return Inertia::render('articles/edit', [
            'article' => $article->only(['id', 'title', 'subtitle', 'body', 'category_id', 'lead_media_id']),
            'categories' => $this->writableCategories($request),
            'media' => $this->pickableMedia($request),
        ]);
    }

    public function update(ArticleRequest $request, Article $article)
    {
        $this->authorize('update', $article); // slug and status are never touched here

        DB::transaction(function () use ($request, $article) {
            $article->update($request->safe()->only(['category_id', 'title', 'subtitle', 'body', 'lead_media_id']));
            AuditLog::record('article.updated', $article, [], $request->user());
        });

        return redirect()->route('articles.show', $article)->with('success', 'Changes saved.');
    }

    public function transition(Request $request, Article $article, ArticleWorkflow $workflow)
    {
        $this->authorize('view', $article);

        $data = $request->validate([
            'to' => ['required', Rule::enum(S::class)],
            'reason' => ['nullable', 'string', 'max:2000'],
            'scheduled_for' => ['nullable', 'date'],
        ]);

        try {
            $workflow->transition(
                $article,
                S::from($data['to']),
                $request->user(),
                $data['reason'] ?? null,
                ! empty($data['scheduled_for']) ? Carbon::parse($data['scheduled_for']) : null,
            );
        } catch (InvalidStatusTransition $e) {
            return back()->withErrors(['transition' => $e->getMessage()]);
        }

        return redirect()->route('articles.show', $article)->with('success', 'Status updated.');
    }

    /** @return list<array{to:string,label:string,needs_reason:bool,needs_schedule:bool,danger:bool}> */
    private function actionsFor(Request $request, Article $article): array
    {
        $user = $request->user();
        $override = $user->hasRole(Role::Admin);

        return collect($article->status->allowedNext())
            ->filter(fn (S $to) => Gate::forUser($user)->allows('transition', [$article, $to]))
            ->map(fn (S $to) => [
                'to' => $to->value,
                'label' => self::LABELS[$to->value].($override ? ' (override)' : ''),
                'needs_reason' => S::requiresReason($to) || $override,
                'needs_schedule' => $to === S::Scheduled,
                'danger' => in_array($to, [S::Rejected, S::RevisionRequested], true),
            ])->values()->all();
    }

    /** Photos the writer may attach: their own uploads (Admin and Editor-in-Chief may use any). */
    private function pickableMedia(Request $request): array
    {
        $user = $request->user();

        return Media::query()
            ->when(! $user->hasRole(Role::Admin, Role::EditorInChief), fn ($q) => $q->where('uploader_id', $user->id))
            ->latest('id')->limit(60)->get()
            ->map(fn (Media $m) => ['id' => $m->id, 'url' => $m->url('thumb'), 'alt' => $m->alt, 'is_public' => $m->is_public])->all();
    }

    private function writableCategories(Request $request): array
    {
        $user = $request->user();

        $query = $user->hasRole(Role::CategoryHead)
            ? $user->categories()->orderBy('sort_order')
            : Category::query()->where('is_active', true)->orderBy('sort_order');

        return $query->get(['categories.id', 'categories.name'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->all();
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title) ?: 'story', 80, '');
        $slug = $base;
        for ($i = 2; Article::withTrashed()->where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
