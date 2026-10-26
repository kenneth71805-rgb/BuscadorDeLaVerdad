<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Gallery;
use App\Models\Issue;
use App\Models\Media;
use App\Models\User;
use App\Services\MediaStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Storage::fake('local'));

function galleryPhoto(bool $public, string $alt = 'A photo'): Media
{
    return app(MediaStore::class)->store(UploadedFile::fake()->image('p.jpg', 900, 600), User::factory()->editor()->create(), $alt, null, null, $public);
}

function makeGallery(string $title, string $slug, string $status, array $mediaIds): Gallery
{
    $g = new Gallery(['title' => $title, 'description' => null]);
    $g->forceFill(['slug' => $slug, 'status' => $status, 'published_at' => $status === 'published' ? now() : null])->save();
    $g->items()->sync(collect($mediaIds)->values()->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => $i]])->all());

    return $g;
}

function plainPdf(string $extra = ''): string
{
    return "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R $extra >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n"
        ."3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";
}

function issuePayload(array $o = []): array
{
    return array_merge([
        'title' => 'October 2026 Issue', 'issue_date' => '2026-10-01', 'description' => 'The election issue.',
        'is_public' => true, 'pdf' => UploadedFile::fake()->createWithContent('layout-final-v7.pdf', plainPdf()),
    ], $o);
}

// ---------- galleries ----------

it('lets only the admin and editor-in-chief manage galleries', function () {
    $this->actingAs(User::factory()->head()->create())->get('/newsroom/galleries')->assertForbidden();
    $this->actingAs(User::factory()->head()->create())->post('/newsroom/galleries', ['title' => 'X', 'status' => 'draft'])->assertForbidden();
    $this->actingAs(User::factory()->editor()->create())->get('/newsroom/galleries')->assertOk();
});

it('shows a published gallery to the public with only its approved photos, in the chosen order', function () {
    $first = galleryPhoto(true, 'First');
    $second = galleryPhoto(true, 'Second');
    $private = galleryPhoto(false, 'Private');

    $this->actingAs(User::factory()->editor()->create())->post('/newsroom/galleries', [
        'title' => 'Foundation Day', 'description' => 'Highlights.', 'status' => 'published', 'items' => [$second->id, $private->id, $first->id],
    ])->assertRedirect('/newsroom/galleries');

    $gallery = Gallery::firstOrFail();
    expect($gallery->slug)->toBe('foundation-day')->and($gallery->published_at)->not->toBeNull();

    $this->get('/galleries')->assertInertia(fn (Assert $p) => $p->has('galleries.data', 1)->where('galleries.data.0.count', 2));
    $this->get('/galleries/foundation-day')->assertInertia(fn (Assert $p) => $p
        ->has('photos', 2)->where('photos.0.alt', 'Second')->where('photos.1.alt', 'First'));
});

it('keeps draft galleries and galleries with no approved photos off the public site', function () {
    $public = galleryPhoto(true);
    $private = galleryPhoto(false);
    makeGallery('Draft', 'draft', 'draft', [$public->id]);
    makeGallery('Hidden', 'hidden', 'published', [$private->id]);

    $this->get('/galleries')->assertInertia(fn (Assert $p) => $p->has('galleries.data', 0));
    $this->get('/galleries/draft')->assertNotFound();
    $this->get('/galleries/hidden')->assertNotFound();
});

// ---------- issues ----------

it('stores an issue PDF privately under a generated name and lists it publicly', function () {
    $this->actingAs(User::factory()->editor()->create())->post('/newsroom/issues', issuePayload())->assertRedirect('/newsroom/issues');

    $issue = Issue::firstOrFail();
    expect($issue->slug)->toBe('october-2026-issue')->and($issue->pdf_path)->toStartWith('issues/')->and($issue->pdf_path)->not->toContain('layout-final');
    Storage::disk('local')->assertExists($issue->pdf_path);

    $this->get('/issues')->assertInertia(fn (Assert $p) => $p->has('issues.data', 1));
    $this->get('/issues/october-2026-issue')->assertOk()->assertInertia(fn (Assert $p) => $p->where('issue.title', 'October 2026 Issue'));
});

it('serves a public issue as a download only, and keeps private issues private', function () {
    $editor = User::factory()->editor()->create();
    $this->actingAs($editor)->post('/newsroom/issues', issuePayload(['is_public' => false]))->assertRedirect();

    auth()->logout();
    $this->get('/issues')->assertInertia(fn (Assert $p) => $p->has('issues.data', 0));
    $this->get('/issues/october-2026-issue')->assertNotFound();
    $this->get('/issues/october-2026-issue/download')->assertNotFound();
    $this->actingAs(User::factory()->head()->create())->get('/issues/october-2026-issue/download')->assertNotFound();
    $this->actingAs($editor)->get('/issues/october-2026-issue/download')->assertOk(); // staff preview

    Issue::firstOrFail()->forceFill(['is_public' => true])->save();
    auth()->logout();
    $response = $this->get('/issues/october-2026-issue/download')->assertOk();
    expect($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->and($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
});

it('refuses fake or dangerous PDFs', function () {
    $editor = User::factory()->editor()->create();

    $this->actingAs($editor)->post('/newsroom/issues', issuePayload(['pdf' => UploadedFile::fake()->createWithContent('a.pdf', 'just some text')]))->assertSessionHasErrors('pdf');
    $this->actingAs($editor)->post('/newsroom/issues', issuePayload(['pdf' => UploadedFile::fake()->createWithContent('b.pdf', plainPdf('/Names << /JavaScript 9 0 R >>'))]))->assertSessionHasErrors('pdf');
    $this->actingAs($editor)->post('/newsroom/issues', issuePayload(['pdf' => UploadedFile::fake()->createWithContent('c.pdf', "<?php system('id'); ?>")]))->assertSessionHasErrors('pdf');

    expect(Issue::count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('replaces an issue PDF and deletes the old file; keeps it when none is uploaded', function () {
    $editor = User::factory()->editor()->create();
    $this->actingAs($editor)->post('/newsroom/issues', issuePayload())->assertRedirect();
    $issue = Issue::firstOrFail();
    $old = $issue->pdf_path;
    $payload = issuePayload(['title' => 'October 2026 Issue (revised)']);

    $this->actingAs($editor)->put("/newsroom/issues/{$issue->id}", array_merge($payload, ['pdf' => null]))->assertRedirect();
    expect($issue->fresh()->pdf_path)->toBe($old)->and($issue->fresh()->slug)->toBe('october-2026-issue');

    $this->actingAs($editor)->put("/newsroom/issues/{$issue->id}", $payload)->assertRedirect();
    expect($issue->fresh()->pdf_path)->not->toBe($old);
    Storage::disk('local')->assertMissing($old);
    Storage::disk('local')->assertExists($issue->fresh()->pdf_path);
});

it('shows a cover only when it is approved for public release', function () {
    $cover = galleryPhoto(false);
    $this->actingAs(User::factory()->editor()->create())->post('/newsroom/issues', issuePayload(['cover_media_id' => $cover->id]))->assertRedirect();

    $this->get('/issues/october-2026-issue')->assertInertia(fn (Assert $p) => $p->where('issue.cover', null));

    $cover->forceFill(['is_public' => true])->save();
    $this->get('/issues/october-2026-issue')->assertInertia(fn (Assert $p) => $p->where('issue.cover.url', $cover->url('medium')));
});

it('keeps issue management away from heads and guests', function () {
    $this->get('/newsroom/issues')->assertRedirect('/login');
    $this->actingAs(User::factory()->head()->create())->post('/newsroom/issues', issuePayload())->assertForbidden();
    expect(Issue::count())->toBe(0);
});

// ---------- archives ----------

it('archives only published stories, by year, with a section filter', function () {
    $news = Category::factory()->create(['slug' => 'news-x']);
    $sports = Category::factory()->create(['slug' => 'sports-x']);
    $author = User::factory()->head()->create();
    $make = fn (Category $c, string $status, ?Carbon $when) => Article::factory()->for($c)->for($author, 'author')->status($status, $when)->create();

    $make($news, 'published', Carbon::create(2024, 5, 1));
    $make($sports, 'published', Carbon::create(2024, 8, 1));
    $make($news, 'published', Carbon::create(2025, 2, 1));
    $make($news, 'draft', Carbon::create(2025, 3, 1));
    $make($news, 'published', now()->addYear());

    $this->get('/archives')->assertInertia(fn (Assert $p) => $p
        ->has('years', 2)->where('years.0.year', 2025)->where('years.0.count', 1)->where('years.1.year', 2024)->where('years.1.count', 2));
    $this->get('/archives/2024')->assertInertia(fn (Assert $p) => $p->has('articles.data', 2));
    $this->get('/archives/2024?category=sports-x')->assertInertia(fn (Assert $p) => $p->has('articles.data', 1)->where('category', 'sports-x'));
    $this->get('/archives/1999')->assertNotFound();
});

it('puts photos and issues on the home page only when they are public', function () {
    $public = galleryPhoto(true);
    makeGallery('Shown', 'shown', 'published', [$public->id]);

    $this->actingAs(User::factory()->editor()->create())->post('/newsroom/issues', issuePayload(['title' => 'Private one', 'is_public' => false]));
    $this->post('/newsroom/issues', issuePayload(['title' => 'Public one']));
    auth()->logout();

    $this->get('/')->assertInertia(fn (Assert $p) => $p->has('galleries', 1)->has('issues', 1)->where('issues.0.title', 'Public one'));
});
