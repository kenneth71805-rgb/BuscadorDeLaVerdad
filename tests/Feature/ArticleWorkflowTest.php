<?php

use App\Enums\ArticleStatus as S;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\User;
use App\Services\ArticleWorkflow;
use Illuminate\Auth\Access\AuthorizationException;

function headOf(Category $category, bool $review = true): User
{
    $head = User::factory()->head()->create();
    $head->categories()->attach($category, ['can_review' => $review]);

    return $head;
}

function articleIn(Category $category, User $author, S $status = S::Draft, $publishedAt = null): Article
{
    return Article::factory()
        ->for($category)->for($author, 'author')
        ->status($status->value, $publishedAt)->create();
}

beforeEach(fn () => $this->workflow = app(ArticleWorkflow::class));

it('lets a head submit a draft in their own category and records history and audit', function () {
    $cat = Category::factory()->create();
    $head = headOf($cat);
    $article = articleIn($cat, $head);

    $this->workflow->transition($article, S::Submitted, $head);

    expect($article->fresh()->status)->toBe(S::Submitted);
    expect($article->statusHistory()->count())->toBe(1);
    expect(AuditLog::where('action', 'article.status_changed')->count())->toBe(1);
});

it('blocks a head from touching another category', function () {
    $mine = Category::factory()->create();
    $other = Category::factory()->create();
    $head = headOf($mine);
    $article = articleIn($other, User::factory()->head()->create());

    $this->workflow->transition($article, S::Submitted, $head);
})->throws(AuthorizationException::class);

it('rejects illegal jumps even for the editor-in-chief', function () {
    $cat = Category::factory()->create();
    $article = articleIn($cat, User::factory()->head()->create());

    $this->workflow->transition($article, S::Published, User::factory()->editor()->create());
})->throws(InvalidStatusTransition::class);

it('requires a reason to reject or return an article', function () {
    $cat = Category::factory()->create();
    $article = articleIn($cat, User::factory()->head()->create(), S::InReview);
    $editor = User::factory()->editor()->create();

    expect(fn () => $this->workflow->transition($article, S::Rejected, $editor))
        ->toThrow(InvalidStatusTransition::class);
    expect(fn () => $this->workflow->transition($article, S::RevisionRequested, $editor, '  '))
        ->toThrow(InvalidStatusTransition::class);

    $this->workflow->transition($article, S::RevisionRequested, $editor, 'Needs a second source.');
    expect($article->fresh()->statusHistory->last()->reason)->toBe('Needs a second source.');
});

it('does not let a head approve their own article, but lets another reviewing head', function () {
    $cat = Category::factory()->create();
    $head = headOf($cat);
    $article = articleIn($cat, $head, S::Submitted);

    expect(fn () => $this->workflow->transition($article, S::CategoryApproved, $head))
        ->toThrow(AuthorizationException::class);

    $this->workflow->transition($article, S::CategoryApproved, headOf($cat));
    expect($article->fresh()->status)->toBe(S::CategoryApproved);
});

it('keeps final approval and publishing with the editor-in-chief by default', function () {
    $cat = Category::factory()->create();
    $head = headOf($cat);
    $article = articleIn($cat, $head, S::CategoryApproved);

    expect(fn () => $this->workflow->transition($article, S::InReview, $head))
        ->toThrow(AuthorizationException::class);

    $editor = User::factory()->editor()->create();
    $this->workflow->transition($article, S::InReview, $editor);
    $this->workflow->transition($article, S::Approved, $editor);

    expect(fn () => $this->workflow->transition($article, S::Published, $head))
        ->toThrow(AuthorizationException::class);

    $this->workflow->transition($article, S::Published, $editor);
    expect(Article::publiclyVisible()->whereKey($article->id)->exists())->toBeTrue();
});

it('keeps scheduled articles private until their time', function () {
    $cat = Category::factory()->create();
    $article = articleIn($cat, User::factory()->head()->create(), S::Approved);
    $editor = User::factory()->editor()->create();

    $this->workflow->transition($article, S::Scheduled, $editor, null, now()->addHours(2));
    expect(Article::publiclyVisible()->count())->toBe(0);
    expect($this->workflow->publishDueScheduled())->toBe(0);

    $this->travel(3)->hours();
    expect($this->workflow->publishDueScheduled())->toBe(1);
    expect(Article::publiclyVisible()->count())->toBe(1);
});

it('never exposes drafts, in-review items or inactive-category articles publicly', function () {
    $cat = Category::factory()->create();
    $off = Category::factory()->create(['is_active' => false]);
    $author = User::factory()->head()->create();

    foreach ([S::Draft, S::Submitted, S::InReview, S::Approved, S::Rejected, S::Archived] as $s) {
        articleIn($cat, $author, $s, now()->subDay());
    }
    articleIn($off, $author, S::Published, now()->subDay());
    articleIn($cat, $author, S::Published, now()->addDay()); // future publish date

    expect(Article::publiclyVisible()->count())->toBe(0);

    articleIn($cat, $author, S::Published, now()->subDay());
    expect(Article::publiclyVisible()->count())->toBe(1);
});

it('lets an admin act only with the override permission, a reason, and an audit trail', function () {
    $cat = Category::factory()->create();
    $article = articleIn($cat, User::factory()->head()->create(), S::InReview);

    $plain = User::factory()->admin()->create();
    expect(fn () => $this->workflow->transition($article, S::Approved, $plain, 'x'))
        ->toThrow(AuthorizationException::class);

    $admin = User::factory()->admin()->create();
    $admin->forceFill(['can_override' => true])->save();

    expect(fn () => $this->workflow->transition($article, S::Approved, $admin))
        ->toThrow(InvalidStatusTransition::class);

    $this->workflow->transition($article, S::Approved, $admin, 'EIC unavailable before deadline.');
    expect(AuditLog::where('action', 'article.override')->count())->toBe(1);
});

it('does not let users grant themselves roles through mass assignment', function () {
    $user = User::create(['name' => 'X', 'email' => 'x@example.com', 'password' => 'secret-pass-1',
        'role' => 'admin', 'can_override' => true]);

    expect($user->fresh()->role)->toBeNull();
    expect($user->fresh()->can_override)->toBeFalse();
});
