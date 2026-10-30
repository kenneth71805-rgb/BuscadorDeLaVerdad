<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Services\MediaStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function seoStory(Category $c, string $status = 'published', array $attrs = []): Article
{
    return Article::factory()->for($c)->for(User::factory()->head()->create(), 'author')
        ->status($status, $status === 'published' ? now()->subHour() : null)->create($attrs);
}

// ---------- security headers ----------

it('sends protective headers on public pages', function () {
    $this->get('/')->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
});

it('leaves the content security policy off by default, and adds it with a matching nonce when enabled', function () {
    $this->get('/')->assertHeaderMissing('Content-Security-Policy')->assertHeaderMissing('Content-Security-Policy-Report-Only');

    config(['security.csp' => 'report-only']);
    $report = $this->get('/')->assertHeader('Content-Security-Policy-Report-Only')->assertHeaderMissing('Content-Security-Policy');

    config(['security.csp' => 'enforce']);
    $response = $this->get('/')->assertHeader('Content-Security-Policy')->assertHeaderMissing('Content-Security-Policy-Report-Only');
    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("default-src 'self'")->toContain("object-src 'none'")->toContain("frame-ancestors 'self'")->not->toContain('unsafe-eval');

    preg_match("/'nonce-([^']+)'/", $csp, $m);
    expect($m)->not->toBeEmpty();
    expect($response->getContent())->toContain('nonce="'.$m[1].'"'); // the inline route script carries the same nonce
});

it('sends the HTTPS-only header only over HTTPS and only when switched on', function () {
    $this->get('https://localhost/')->assertHeaderMissing('Strict-Transport-Security');

    config(['security.hsts' => true]);
    $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security');
});

it('keeps account and staff pages out of search engines, but not the public site', function () {
    $this->get('/')->assertHeaderMissing('X-Robots-Tag');
    $this->get('/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');

    $this->actingAs(User::factory()->editor()->create())->get('/editor')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('signs a person out everywhere when an admin resets their password', function () {
    config(['session.driver' => 'database']);
    $admin = User::factory()->admin()->create();
    $member = User::factory()->editor()->create();
    $other = User::factory()->editor()->create();
    foreach ([$member, $other] as $u) {
        DB::table('sessions')->insert(['id' => 'sess-'.$u->id, 'user_id' => $u->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'x', 'payload' => 'x', 'last_activity' => time()]);
    }

    $this->actingAs($admin)->put("/admin/staff/{$member->id}", [
        'name' => $member->name, 'email' => $member->email, 'role' => 'editor_in_chief', 'is_active' => true,
        'position' => null, 'password' => 'a-brand-new-pass-123',
    ])->assertRedirect('/admin/staff');

    expect(DB::table('sessions')->where('user_id', $member->id)->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $other->id)->count())->toBe(1);
});

// ---------- robots and sitemap ----------

it('serves a robots file that hides staff pages and points to the sitemap', function () {
    $response = $this->get('/robots.txt')->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('text/plain');
    expect($response->getContent())->toContain('Disallow: /admin')->toContain('Disallow: /articles')->toContain('Sitemap: '.url('/sitemap.xml'));
});

it('lists only public pages in the sitemap', function () {
    Cache::forget('sitemap.xml');
    $open = Category::factory()->create(['slug' => 'open-news']);
    $closed = Category::factory()->create(['slug' => 'closed-news', 'is_active' => false]);
    $live = seoStory($open, 'published', ['slug' => 'live-story']);
    seoStory($open, 'draft', ['slug' => 'secret-draft']);
    seoStory($closed, 'published', ['slug' => 'hidden-section-story']);

    $response = $this->get('/sitemap.xml')->assertOk();
    $xml = $response->getContent();

    expect($response->headers->get('Content-Type'))->toContain('xml');
    expect($xml)->toContain('<loc>'.url('/open-news/live-story').'</loc>')->toContain('<loc>'.url('/open-news').'</loc>')->toContain('<loc>'.url('/about').'</loc>')
        ->not->toContain('secret-draft')->not->toContain('hidden-section-story')->not->toContain('closed-news');
    expect(simplexml_load_string($xml))->not->toBeFalse(); // well-formed XML
});

// ---------- page details written on the server ----------

it('writes canonical, share-preview and article details into the page itself', function () {
    $cat = Category::factory()->create(['slug' => 'campus']);
    $article = seoStory($cat, 'published', ['title' => 'Plain title', 'subtitle' => 'A short subtitle', 'slug' => 'plain-title']);
    $path = '/campus/plain-title';

    $html = $this->get($path)->assertOk()->getContent();
    expect($html)->toContain('<link rel="canonical" href="'.url($path).'">')
        ->toContain('<meta property="og:title" content="Plain title">')
        ->toContain('<meta property="og:type" content="article">')
        ->toContain('<meta name="description" content="A short subtitle">')
        ->toContain('application/ld+json')->toContain('"@type":"NewsArticle"');
});

it('only offers a share-preview image when the photo is approved for the public', function () {
    Storage::fake('local');
    $cat = Category::factory()->create(['slug' => 'campus']);
    $editor = User::factory()->editor()->create();
    $photo = app(MediaStore::class)->store(UploadedFile::fake()->image('p.jpg', 1200, 800), $editor, 'A photo');
    seoStory($cat, 'published', ['slug' => 'with-photo', 'lead_media_id' => $photo->id]);

    $this->get('/campus/with-photo')->assertInertia(fn (Assert $p) => $p->where('meta.image', null));

    $photo->forceFill(['is_public' => true])->save();
    $this->get('/campus/with-photo')->assertInertia(fn (Assert $p) => $p->where('meta.image', url($photo->url('large'))));
});

it('cannot be broken out of by story text in page details', function () {
    $cat = Category::factory()->create(['slug' => 'campus']);
    seoStory($cat, 'published', ['title' => '</script><script>alert(1)</script>', 'slug' => 'tricky']);

    $html = $this->get('/campus/tricky')->assertOk()->getContent();

    expect($html)->not->toContain('<script>alert(1)')->not->toContain('</script><script>alert');
});

it('turns story text into a plain-text description', function () {
    $cat = Category::factory()->create(['slug' => 'campus']);
    seoStory($cat, 'published', ['slug' => 'html-body', 'subtitle' => null, 'body' => "<b>Bold</b> opening   line\n\nof the story, long enough to read."]);

    $this->get('/campus/html-body')->assertInertia(fn (Assert $p) => $p->where('meta.description', 'Bold opening line of the story, long enough to read.'));
});

it('keeps search results out of search engines and gives each page of a list its own address', function () {
    $cat = Category::factory()->create(['slug' => 'campus']);

    $this->get('/search?q=anything')->assertInertia(fn (Assert $p) => $p->where('meta.robots', 'noindex,follow'));
    expect($this->get('/search?q=anything')->getContent())->toContain('<meta name="robots" content="noindex,follow">');

    $this->get('/campus')->assertInertia(fn (Assert $p) => $p->where('meta.canonical', url('/campus')));
    $this->get('/campus?page=2')->assertInertia(fn (Assert $p) => $p->where('meta.canonical', url('/campus?page=2')));
});
