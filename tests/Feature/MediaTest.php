<?php

use App\Models\Article;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Media;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\MediaStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => Storage::fake('local'));

function photoFor(User $user, bool $public = false, string $alt = 'A crowd at the plaza'): Media
{
    return app(MediaStore::class)->store(UploadedFile::fake()->image('p.jpg', 1200, 800), $user, $alt, 'A caption', 'Maria Cruz', $public);
}

function mediaHead(Category $category): User
{
    $head = User::factory()->head()->create();
    $head->categories()->attach($category);

    return $head;
}

function articleWithPhoto(Category $c, User $author, Media $m, string $status = 'approved'): Article
{
    return Article::factory()->for($c)->for($author, 'author')->status($status, $status === 'published' ? now()->subHour() : null)->create(['lead_media_id' => $m->id]);
}

// ---------- uploads ----------

it('stores an uploaded photo privately, in four sizes, under a safe generated name', function () {
    $head = User::factory()->head()->create();

    $this->actingAs($head)->post('/media', [
        'file' => UploadedFile::fake()->image('../../Bad Name.jpg', 1600, 1000),
        'alt' => 'Students at the plaza', 'caption' => 'Opening day', 'credit' => 'Maria Cruz',
    ])->assertRedirect()->assertSessionHas('success');

    $m = Media::firstOrFail();
    expect($m->is_public)->toBeFalse()->and($m->uploader_id)->toBe($head->id)->and($m->credit)->toBe('Maria Cruz')
        ->and($m->path)->not->toContain('Bad')->and($m->path)->not->toContain('..');
    foreach (['thumb', 'medium', 'large', 'original'] as $v) {
        Storage::disk('local')->assertExists("{$m->path}/{$v}.jpg");
    }
});

it('requires a description for every photo', function () {
    $this->actingAs(User::factory()->head()->create())
        ->post('/media', ['file' => UploadedFile::fake()->image('a.jpg'), 'alt' => ''])->assertSessionHasErrors('alt');

    expect(Media::count())->toBe(0);
});

it('rejects scripts and unsafe formats, whatever they are called', function () {
    $head = User::factory()->head()->create();

    $this->actingAs($head)->post('/media', ['file' => UploadedFile::fake()->createWithContent('evil.jpg', '<?php echo shell_exec("id"); ?>'), 'alt' => 'x'])->assertSessionHasErrors('file');
    $this->actingAs($head)->post('/media', ['file' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'), 'alt' => 'x'])->assertSessionHasErrors('file');
    $this->actingAs($head)->post('/media', ['file' => UploadedFile::fake()->create('big.jpg', 9000, 'image/jpeg'), 'alt' => 'x'])->assertSessionHasErrors('file');

    expect(Media::count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBe([]);
});

// ---------- who can see a file ----------

it('hides unapproved photos from everyone except the uploader and senior editors', function () {
    $owner = User::factory()->head()->create();
    $m = photoFor($owner);

    $this->get($m->url('medium'))->assertNotFound();                                              // guest
    $this->actingAs(User::factory()->head()->create())->get($m->url('medium'))->assertNotFound(); // another head
    $this->actingAs($owner)->get($m->url('medium'))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    $this->actingAs(User::factory()->editor()->create())->get($m->url('thumb'))->assertOk();
    $this->actingAs($owner)->get("/media/{$m->id}/evil")->assertNotFound();
});

it('serves approved photos to the public', function () {
    $m = photoFor(User::factory()->head()->create(), true);

    $this->get($m->url('large'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('lets only admins and the editor-in-chief approve or withdraw photos, with an audit trail', function () {
    $owner = User::factory()->head()->create();
    $m = photoFor($owner);

    $this->actingAs($owner)->put("/media/{$m->id}/release", ['public' => true])->assertForbidden();
    $this->actingAs(User::factory()->editor()->create())->put("/media/{$m->id}/release", ['public' => true])->assertRedirect();
    expect($m->fresh()->is_public)->toBeTrue();

    $this->actingAs(User::factory()->editor()->create())->put("/media/{$m->id}/release", ['public' => false])->assertRedirect();
    expect($m->fresh()->is_public)->toBeFalse()
        ->and(AuditLog::whereIn('action', ['media.released', 'media.withdrawn'])->count())->toBe(2);
});

it('limits the library list for a head to their own uploads', function () {
    $a = User::factory()->head()->create();
    photoFor($a);
    photoFor(User::factory()->head()->create());

    $this->get('/media')->assertRedirect('/login'); // guest first: actingAs would otherwise stay signed in
    $this->actingAs($a)->get('/media')->assertInertia(fn (Assert $p) => $p->component('media/index')->has('items.data', 1));
    $this->actingAs(User::factory()->editor()->create())->get('/media')->assertInertia(fn (Assert $p) => $p->has('items.data', 2));
});

it('stops a head from editing a photo after it was approved', function () {
    $owner = User::factory()->head()->create();
    $m = photoFor($owner, true);

    $this->actingAs($owner)->put("/media/{$m->id}", ['alt' => 'Changed'])->assertForbidden();
});

// ---------- deleting ----------

it('refuses to delete a photo that is in use, and removes files when it is not', function () {
    $cat = Category::factory()->create();
    $head = mediaHead($cat);
    $used = photoFor($head);
    articleWithPhoto($cat, $head, $used, 'draft');
    $free = photoFor($head);

    $this->actingAs($head)->delete("/media/{$used->id}")->assertSessionHasErrors('delete');
    expect(Media::whereKey($used->id)->exists())->toBeTrue();

    $path = $free->path;
    $this->actingAs($head)->delete("/media/{$free->id}")->assertRedirect();
    expect(Media::whereKey($free->id)->exists())->toBeFalse();
    Storage::disk('local')->assertMissing("{$path}/thumb.jpg");
});

// ---------- lead photos on articles ----------

it('lets a head attach only their own photos to an article', function () {
    $cat = Category::factory()->create();
    $head = mediaHead($cat);
    $mine = photoFor($head);
    $theirs = photoFor(User::factory()->head()->create());
    $payload = ['title' => 'Fair opens', 'body' => 'The fair opened on Monday with a parade.', 'category_id' => $cat->id];

    $this->actingAs($head)->post('/articles', $payload + ['lead_media_id' => $theirs->id])->assertSessionHasErrors('lead_media_id');
    $this->actingAs($head)->post('/articles', $payload + ['lead_media_id' => $mine->id])->assertRedirect();

    expect(Article::firstOrFail()->lead_media_id)->toBe($mine->id);
});

it('blocks publishing until the lead photo is approved, then shows it publicly, and hides it if withdrawn', function () {
    $cat = Category::factory()->create();
    $head = mediaHead($cat);
    $editor = User::factory()->editor()->create();
    $photo = photoFor($head);
    $article = articleWithPhoto($cat, $head, $photo);

    $this->actingAs($editor)->post("/articles/{$article->id}/transition", ['to' => 'published'])->assertSessionHasErrors('transition');
    expect($article->fresh()->status->value)->toBe('approved');

    $this->actingAs($editor)->put("/media/{$photo->id}/release", ['public' => true]);
    $this->actingAs($editor)->post("/articles/{$article->id}/transition", ['to' => 'published'])->assertRedirect();

    $this->get("/{$cat->slug}/{$article->slug}")->assertInertia(fn (Assert $p) => $p->where('article.lead.url', $photo->url('large')));
    $this->get('/')->assertInertia(fn (Assert $p) => $p->where('lead.image.url', $photo->url('medium')));

    $this->actingAs($editor)->put("/media/{$photo->id}/release", ['public' => false]);
    $this->get("/{$cat->slug}/{$article->slug}")->assertInertia(fn (Assert $p) => $p->where('article.lead', null));
    $this->get('/')->assertInertia(fn (Assert $p) => $p->where('lead.image', null));
});

// ---------- profile photos ----------

it('lets an admin add, replace and remove a profile portrait', function () {
    $admin = User::factory()->admin()->create();
    $base = ['name' => 'Ana Reyes', 'position' => 'Head, News', 'sort_order' => 1, 'is_public' => true, 'archived' => false];

    $this->actingAs($admin)->post('/admin/profiles', $base + ['photo' => UploadedFile::fake()->image('a.png', 500, 500)])->assertRedirect();
    $profile = StaffProfile::firstOrFail();
    $first = $profile->photo;
    expect($first->is_public)->toBeTrue();

    $this->get('/about')->assertInertia(fn (Assert $p) => $p->where('current.0.photo.url', $first->url('medium')));

    $this->actingAs($admin)->put("/admin/profiles/{$profile->id}", $base + ['photo' => UploadedFile::fake()->image('b.png', 500, 500)])->assertRedirect();
    $second = $profile->fresh()->photo;
    expect($second->id)->not->toBe($first->id)->and(Media::whereKey($first->id)->exists())->toBeFalse();
    Storage::disk('local')->assertMissing("{$first->path}/thumb.jpg");

    $this->actingAs($admin)->put("/admin/profiles/{$profile->id}", $base + ['remove_photo' => true])->assertRedirect();
    expect($profile->fresh()->photo_media_id)->toBeNull()->and(Media::count())->toBe(0);
});

it('rejects a profile portrait that is not a real image', function () {
    $this->actingAs(User::factory()->admin()->create())->post('/admin/profiles', [
        'name' => 'X', 'position' => 'Y', 'sort_order' => 0, 'is_public' => true, 'archived' => false,
        'photo' => UploadedFile::fake()->createWithContent('p.png', '<?php phpinfo();'),
    ])->assertSessionHasErrors('photo');
});
