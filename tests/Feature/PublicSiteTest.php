<?php

use App\Enums\ArticleStatus as S;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function pubStory(Category $category, S $status = S::Published, $publishedAt = 'past', array $attrs = []): Article
{
    $date = $publishedAt === 'past' ? now()->subHour() : $publishedAt;

    return Article::factory()->for($category)->for(User::factory()->head()->create(), 'author')
        ->status($status->value, $date)->create($attrs);
}

it('shows a purposeful empty state when nothing is published', function () {
    $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('site/home')->where('lead', null)->has('latest', 0)->has('sections', 0));
});

it('shows published stories on the home page, newest as the lead', function () {
    $cat = Category::factory()->create();
    pubStory($cat, S::Published, now()->subDays(2), ['title' => 'Older story']);
    pubStory($cat, S::Published, now()->subHour(), ['title' => 'Newest story']);

    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->where('lead.title', 'Newest story')->has('latest', 1)->where('latest.0.title', 'Older story'));
});

it('serves a published story at its stable URL and 404s everything else', function () {
    $cat = Category::factory()->create();
    $live = pubStory($cat, S::Published, 'past', ['slug' => 'open-story']);
    pubStory($cat, S::Draft, null, ['slug' => 'secret-draft']);
    pubStory($cat, S::InReview, null, ['slug' => 'in-review']);
    pubStory($cat, S::Published, now()->addDay(), ['slug' => 'future-story']);

    $this->get("/{$cat->slug}/open-story")->assertOk()->assertInertia(fn (Assert $page) => $page->component('site/article')->where('article.title', $live->title));
    $this->get("/{$cat->slug}/secret-draft")->assertNotFound();
    $this->get("/{$cat->slug}/in-review")->assertNotFound();
    $this->get("/{$cat->slug}/future-story")->assertNotFound();
    $this->get('/wrong-section/open-story')->assertNotFound();
});

it('hides inactive sections and their stories', function () {
    $off = Category::factory()->create(['is_active' => false]);
    pubStory($off, S::Published, 'past', ['slug' => 'hidden-story']);

    $this->get("/{$off->slug}")->assertNotFound();
    $this->get("/{$off->slug}/hidden-story")->assertNotFound();
    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('lead', null));
});

it('lists only published stories on a section page', function () {
    $cat = Category::factory()->create();
    pubStory($cat);
    pubStory($cat, S::Draft, null);
    pubStory($cat, S::Approved, null);

    $this->get("/{$cat->slug}")->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('site/category')->has('articles.data', 1));
});

it('searches only published stories and survives wildcard characters', function () {
    $cat = Category::factory()->create();
    pubStory($cat, S::Published, 'past', ['title' => 'Typhoon relief drive']);
    pubStory($cat, S::Draft, null, ['title' => 'Typhoon draft notes']);

    $this->get('/search?q=typhoon')->assertInertia(fn (Assert $page) => $page
        ->component('site/search')->has('results.data', 1)->where('results.data.0.title', 'Typhoon relief drive'));
    $this->get('/search?q='.urlencode('%%'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('results.data', 0));
    $this->get('/search?q=a')->assertInertia(fn (Assert $page) => $page->where('results', null));
});

it('does not let public catch-all routes swallow real pages', function () {
    $this->get('/login')->assertOk();
    $this->get('/admin')->assertRedirect('/login');
    $this->get('/dashboard')->assertRedirect('/login');
});
