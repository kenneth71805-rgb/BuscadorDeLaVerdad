<?php

use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function corrHead(Category $category): User
{
    $head = User::factory()->head()->create();
    $head->categories()->attach($category);

    return $head;
}

function corrLive(Category $c, User $author, string $status = 'published'): Article
{
    return Article::factory()->for($c)->for($author, 'author')->status($status, $status === 'published' ? now()->subDay() : null)
        ->create(['title' => 'Original headline', 'body' => 'The original body text of the story, long enough.']);
}

function corrPayload(array $o = []): array
{
    return array_merge([
        'title' => 'Corrected headline', 'subtitle' => null,
        'body' => 'The corrected body text of the story, long enough.',
        'reason' => 'The date of the event was wrong.', 'public_note' => 'An earlier version gave the wrong date. It is now corrected.',
    ], $o);
}

it('keeps the live article unchanged while a correction waits, and tells the editor', function () {
    $cat = Category::factory()->create();
    $head = corrHead($cat);
    $editor = User::factory()->editor()->create();
    $article = corrLive($cat, $head);

    $this->actingAs($head)->post("/articles/{$article->id}/correction", corrPayload())->assertRedirect("/articles/{$article->id}");

    $rev = ArticleRevision::firstOrFail();
    expect($rev->status)->toBe('pending')->and($rev->version)->toBe(1)->and($article->fresh()->title)->toBe('Original headline');
    expect($editor->notifications()->count())->toBe(1);

    auth()->logout();
    $this->get("/{$cat->slug}/{$article->slug}")->assertInertia(fn (Assert $p) => $p
        ->where('article.title', 'Original headline')->where('article.corrected_at', null)->has('article.corrections', 0));
});

it('allows only one pending correction at a time', function () {
    $cat = Category::factory()->create();
    $head = corrHead($cat);
    $article = corrLive($cat, $head);

    $this->actingAs($head)->post("/articles/{$article->id}/correction", corrPayload())->assertRedirect();
    $this->actingAs($head)->post("/articles/{$article->id}/correction", corrPayload(['title' => 'Another']))->assertSessionHasErrors('correction');

    expect(ArticleRevision::count())->toBe(1);
});

it('lets only authorized people propose corrections, and only on published articles', function () {
    $cat = Category::factory()->create();
    $other = Category::factory()->create();
    $author = corrHead($cat);
    $live = corrLive($cat, $author);
    $draft = corrLive($cat, $author, 'draft');

    $this->actingAs(corrHead($other))->post("/articles/{$live->id}/correction", corrPayload())->assertForbidden();
    $this->actingAs(corrHead($other))->get("/articles/{$live->id}/correction")->assertForbidden();
    $this->actingAs($author)->post("/articles/{$draft->id}/correction", corrPayload())->assertForbidden();
    $this->actingAs(User::factory()->create())->post("/articles/{$live->id}/correction", corrPayload())->assertForbidden();
    expect(ArticleRevision::count())->toBe(0);
});

it('validates the correction, including the internal reason', function () {
    $cat = Category::factory()->create();
    $head = corrHead($cat);
    $article = corrLive($cat, $head);

    $this->actingAs($head)->post("/articles/{$article->id}/correction", corrPayload(['reason' => '']))->assertSessionHasErrors('reason');
    $this->actingAs($head)->post("/articles/{$article->id}/correction", corrPayload(['body' => 'too short']))->assertSessionHasErrors('body');
    expect(ArticleRevision::count())->toBe(0);
});

it('lets only the editor-in-chief decide, then publishes the new text and keeps the old', function () {
    $cat = Category::factory()->create();
    $head = corrHead($cat);
    $article = corrLive($cat, $head);
    $published = $article->published_at;
    $this->actingAs($head)->post("/articles/{$article->id}/correction", corrPayload());
    $rev = ArticleRevision::firstOrFail();

    $this->actingAs($head)->put("/corrections/{$rev->id}", ['decision' => 'approve'])->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->put("/corrections/{$rev->id}", ['decision' => 'approve'])->assertForbidden();
    expect($article->fresh()->title)->toBe('Original headline');

    $editor = User::factory()->editor()->create();
    $this->actingAs($editor)->put("/corrections/{$rev->id}", ['decision' => 'approve', 'reason' => 'Checked against the source.'])->assertRedirect("/articles/{$article->id}");

    $live = $article->fresh();
    $rev = $rev->fresh();
    expect($live->title)->toBe('Corrected headline')->and($live->corrected_at)->not->toBeNull()
        ->and($live->published_at->equalTo($published))->toBeTrue()          // publication date and web address never change
        ->and($rev->status)->toBe('applied')->and($rev->decided_by)->toBe($editor->id)
        ->and($rev->previous_title)->toBe('Original headline')->and($rev->previous_body)->toContain('original body');
    expect($head->notifications()->count())->toBe(1);
    expect(AuditLog::where('action', 'article.corrected')->count())->toBe(1);
});

it('shows readers an Updated date and the public notice, but never the internal reason', function () {
    $cat = Category::factory()->create();
    $head = corrHead($cat);
    $article = corrLive($cat, $head);
    $this->actingAs($head)->post("/articles/{$article->id}/correction", corrPayload());
    $this->actingAs(User::factory()->editor()->create())->put('/corrections/'.ArticleRevision::firstOrFail()->id, ['decision' => 'approve']);
    auth()->logout();

    $response = $this->get("/{$cat->slug}/{$article->slug}")->assertInertia(fn (Assert $p) => $p
        ->where('article.title', 'Corrected headline')->where('article.corrections.0.note', 'An earlier version gave the wrong date. It is now corrected.'));
    expect($response->getContent())->not->toContain('The date of the event was wrong');
});

it('shows no public notice when none was written, only the Updated date', function () {
    $cat = Category::factory()->create();
    $head = corrHead($cat);
    $article = corrLive($cat, $head);
    $this->actingAs($head)->post("/articles/{$article->id}/correction", corrPayload(['public_note' => null]));
    $this->actingAs(User::factory()->editor()->create())->put('/corrections/'.ArticleRevision::firstOrFail()->id, ['decision' => 'approve']);
    auth()->logout();

    $this->get("/{$cat->slug}/{$article->slug}")->assertInertia(fn (Assert $p) => $p->has('article.corrections', 0)->whereNot('article.corrected_at', null));
});

it('requires a reason to decline, leaves the live text alone, and cannot be decided twice', function () {
    $cat = Category::factory()->create();
    $head = corrHead($cat);
    $article = corrLive($cat, $head);
    $this->actingAs($head)->post("/articles/{$article->id}/correction", corrPayload());
    $rev = ArticleRevision::firstOrFail();
    $editor = User::factory()->editor()->create();

    $this->actingAs($editor)->put("/corrections/{$rev->id}", ['decision' => 'decline'])->assertSessionHasErrors('correction');
    expect($rev->fresh()->status)->toBe('pending');

    $this->actingAs($editor)->put("/corrections/{$rev->id}", ['decision' => 'decline', 'reason' => 'The original date was right.'])->assertRedirect();
    expect($rev->fresh()->status)->toBe('declined')->and($article->fresh()->title)->toBe('Original headline')->and($head->notifications()->count())->toBe(1);

    $this->actingAs($editor)->put("/corrections/{$rev->id}", ['decision' => 'approve'])->assertForbidden();
    $this->actingAs($head)->post("/articles/{$article->id}/correction", corrPayload(['title' => 'Try again']))->assertRedirect(); // a new one is allowed after a decision
    expect(ArticleRevision::count())->toBe(2);
});

it('applies the editor-in-chief\'s own correction at once, as a recorded action', function () {
    $cat = Category::factory()->create();
    $editor = User::factory()->editor()->create();
    $article = corrLive($cat, corrHead($cat));

    $this->actingAs($editor)->post("/articles/{$article->id}/correction", corrPayload())->assertRedirect();

    $rev = ArticleRevision::firstOrFail();
    expect($rev->status)->toBe('applied')->and($article->fresh()->title)->toBe('Corrected headline')->and($editor->notifications()->count())->toBe(0);
    expect(AuditLog::whereIn('action', ['article.correction_proposed', 'article.corrected'])->count())->toBe(2);
});

it('refuses to apply a correction if the article is no longer published', function () {
    $cat = Category::factory()->create();
    $head = corrHead($cat);
    $article = corrLive($cat, $head);
    $this->actingAs($head)->post("/articles/{$article->id}/correction", corrPayload());
    $article->forceFill(['status' => 'archived'])->save();

    $this->actingAs(User::factory()->editor()->create())->put('/corrections/'.ArticleRevision::firstOrFail()->id, ['decision' => 'approve'])->assertSessionHasErrors('correction');

    expect($article->fresh()->title)->toBe('Original headline');
});

it('keeps every version, so the record survives repeated corrections', function () {
    $cat = Category::factory()->create();
    $editor = User::factory()->editor()->create();
    $article = corrLive($cat, corrHead($cat));

    $this->actingAs($editor)->post("/articles/{$article->id}/correction", corrPayload(['title' => 'Second headline']));
    $this->actingAs($editor)->post("/articles/{$article->id}/correction", corrPayload(['title' => 'Third headline']));

    $revs = ArticleRevision::orderBy('version')->get();
    expect($revs->pluck('version')->all())->toBe([1, 2])
        ->and($revs[0]->previous_title)->toBe('Original headline')
        ->and($revs[1]->previous_title)->toBe('Second headline')
        ->and($article->fresh()->title)->toBe('Third headline');
});

it('limits who can open the review page and shows both versions', function () {
    $cat = Category::factory()->create();
    $head = corrHead($cat);
    $article = corrLive($cat, $head);

    $this->get('/corrections/1')->assertRedirect('/login');
    $this->actingAs($head)->post("/articles/{$article->id}/correction", corrPayload());
    $rev = ArticleRevision::firstOrFail();

    $this->actingAs(corrHead(Category::factory()->create()))->get("/corrections/{$rev->id}")->assertForbidden();
    $this->actingAs($head)->get("/corrections/{$rev->id}")->assertInertia(fn (Assert $p) => $p
        ->component('corrections/show')->where('revision.current.title', 'Original headline')->where('revision.proposed.title', 'Corrected headline')->where('can_decide', false));
    $this->actingAs(User::factory()->editor()->create())->get("/corrections/{$rev->id}")->assertInertia(fn (Assert $p) => $p->where('can_decide', true));
});

it('lists pending corrections on the editor dashboard and flags them on the article page', function () {
    $cat = Category::factory()->create();
    $head = corrHead($cat);
    $article = corrLive($cat, $head);
    $this->actingAs($head)->post("/articles/{$article->id}/correction", corrPayload());

    $this->actingAs(User::factory()->editor()->create())->get('/editor')->assertInertia(fn (Assert $p) => $p->has('corrections', 1));
    $this->actingAs($head)->get("/articles/{$article->id}")->assertInertia(fn (Assert $p) => $p->where('can_correct', true)->whereNot('pending_correction', null));
});
