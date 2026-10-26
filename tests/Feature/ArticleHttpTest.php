<?php

use App\Enums\ArticleStatus as S;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function httpHead(Category $category, bool $review = true): User
{
    $head = User::factory()->head()->create();
    $head->categories()->attach($category, ['can_review' => $review]);

    return $head;
}

function httpArticle(Category $category, User $author, S $status = S::Draft, $publishedAt = null): Article
{
    return Article::factory()->for($category)->for($author, 'author')->status($status->value, $publishedAt)->create();
}

function draftPayload(Category $category, array $overrides = []): array
{
    return array_merge([
        'title' => 'Campus election results',
        'subtitle' => null,
        'body' => "First paragraph of the story, long enough.\n\nSecond paragraph.",
        'category_id' => $category->id,
    ], $overrides);
}

it('lets a head save a draft in an assigned category, with history', function () {
    $cat = Category::factory()->create();
    $head = httpHead($cat);

    $this->actingAs($head)->post('/articles', draftPayload($cat))->assertRedirect();

    $article = Article::firstOrFail();
    expect($article->status)->toBe(S::Draft)
        ->and($article->author_id)->toBe($head->id)
        ->and($article->slug)->toBe('campus-election-results')
        ->and($article->statusHistory()->count())->toBe(1);
});

it('keeps the public URL stable when the headline is edited', function () {
    $cat = Category::factory()->create();
    $head = httpHead($cat);
    $article = httpArticle($cat, $head);
    $slug = $article->slug;

    $this->actingAs($head)->put("/articles/{$article->id}", draftPayload($cat, ['title' => 'A brand new headline']))->assertRedirect();

    expect($article->fresh()->title)->toBe('A brand new headline')->and($article->fresh()->slug)->toBe($slug);
});

it('refuses a head writing in a category they are not assigned to, even with a forged category id', function () {
    $mine = Category::factory()->create();
    $other = Category::factory()->create();

    $this->actingAs(httpHead($mine))->post('/articles', draftPayload($other))->assertSessionHasErrors('category_id');

    expect(Article::count())->toBe(0);
});

it('hides other categories from a head, in the list and by direct address', function () {
    $mine = Category::factory()->create();
    $other = Category::factory()->create();
    $head = httpHead($mine);
    $foreign = httpArticle($other, User::factory()->head()->create());
    httpArticle($mine, $head);

    $this->actingAs($head)->get('/articles')->assertInertia(fn (Assert $page) => $page->has('articles.data', 1));
    $this->actingAs($head)->get("/articles/{$foreign->id}")->assertForbidden();
    $this->actingAs($head)->get("/articles/{$foreign->id}/edit")->assertForbidden();
    $this->actingAs($head)->put("/articles/{$foreign->id}", draftPayload($mine))->assertForbidden();
    $this->actingAs($head)->post("/articles/{$foreign->id}/transition", ['to' => 'submitted'])->assertForbidden();
});

it('lets a head edit a draft but not an article already submitted', function () {
    $cat = Category::factory()->create();
    $head = httpHead($cat);
    $draft = httpArticle($cat, $head);
    $submitted = httpArticle($cat, $head, S::Submitted);

    $this->actingAs($head)->put("/articles/{$draft->id}", draftPayload($cat))->assertRedirect();
    $this->actingAs($head)->put("/articles/{$submitted->id}", draftPayload($cat))->assertForbidden();
});

it('runs an article from draft to the public site through the real endpoints', function () {
    $cat = Category::factory()->create();
    $author = httpHead($cat);
    $reviewer = httpHead($cat);
    $editor = User::factory()->editor()->create();
    $article = httpArticle($cat, $author);

    $move = fn (User $user, string $to, array $extra = []) => $this->actingAs($user)
        ->post("/articles/{$article->id}/transition", ['to' => $to] + $extra);

    $move($author, 'submitted')->assertRedirect();
    expect($article->fresh()->status)->toBe(S::Submitted);

    $move($reviewer, 'category_approved')->assertRedirect();
    $move($editor, 'in_review')->assertRedirect();
    $move($editor, 'approved')->assertRedirect();
    expect(Article::publiclyVisible()->count())->toBe(0);

    $move($editor, 'published')->assertRedirect();
    expect($article->fresh()->status)->toBe(S::Published)
        ->and(Article::publiclyVisible()->count())->toBe(1)
        ->and($article->statusHistory()->count())->toBe(5); // five moves (the factory adds no creation row)
});

it('stops a head from publishing without the publish grant', function () {
    $cat = Category::factory()->create();
    $head = httpHead($cat);
    $article = httpArticle($cat, User::factory()->head()->create(), S::Approved);

    $this->actingAs($head)->post("/articles/{$article->id}/transition", ['to' => 'published'])->assertForbidden();

    expect($article->fresh()->status)->toBe(S::Approved);
});

it('requires a reason to reject, and leaves the article unchanged without one', function () {
    $cat = Category::factory()->create();
    $article = httpArticle($cat, User::factory()->head()->create(), S::InReview);
    $editor = User::factory()->editor()->create();

    $this->actingAs($editor)->post("/articles/{$article->id}/transition", ['to' => 'rejected'])->assertSessionHasErrors('transition');
    expect($article->fresh()->status)->toBe(S::InReview);

    $this->actingAs($editor)->post("/articles/{$article->id}/transition", ['to' => 'rejected', 'reason' => 'Unverified claims.'])->assertRedirect();
    expect($article->fresh()->status)->toBe(S::Rejected);
});

it('does not allow editing a live article in place, even by the editor-in-chief', function () {
    $cat = Category::factory()->create();
    $article = httpArticle($cat, User::factory()->head()->create(), S::Published, now()->subDay());

    $this->actingAs(User::factory()->editor()->create())->put("/articles/{$article->id}", draftPayload($cat))->assertForbidden();
});

it('keeps guests and role-less accounts out of the newsroom', function () {
    $this->get('/articles')->assertRedirect('/login');
    $this->actingAs(User::factory()->create())->get('/articles')->assertForbidden();
});

it('offers each person only the actions they may take', function () {
    $cat = Category::factory()->create();
    $head = httpHead($cat);
    $article = httpArticle($cat, $head);

    $this->actingAs($head)->get("/articles/{$article->id}")->assertInertia(fn (Assert $page) => $page
        ->component('articles/show')->has('actions', 1)->where('actions.0.to', 'submitted'));

    $this->actingAs(User::factory()->admin()->create())->get("/articles/{$article->id}")
        ->assertInertia(fn (Assert $page) => $page->has('actions', 0)); // no override permission
});
