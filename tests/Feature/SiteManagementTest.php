<?php

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\ContactSubmission;
use App\Models\Setting;
use App\Models\StaffProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function newCategory(array $o = []): array
{
    return array_merge(['name' => 'Sports', 'description' => null, 'sort_order' => 3, 'is_active' => true, 'show_in_nav' => true], $o);
}

function contactPayload(array $o = []): array
{
    return array_merge([
        'name' => 'Ana Reyes', 'email' => 'ana@example.com', 'type' => 'correction',
        'message' => 'The date in your story about the fair is wrong.', 'consent' => true,
    ], $o);
}

// ---------- categories ----------

it('lets only an admin create categories, with a stable web address', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs(User::factory()->editor()->create())->post('/admin/categories', newCategory())->assertForbidden();
    $this->actingAs($admin)->post('/admin/categories', newCategory())->assertRedirect('/admin/categories');

    $category = Category::where('slug', 'sports')->firstOrFail();
    $this->actingAs($admin)->put("/admin/categories/{$category->id}", newCategory(['name' => 'Sports and Wellness']))->assertRedirect();

    expect($category->fresh()->name)->toBe('Sports and Wellness')->and($category->fresh()->slug)->toBe('sports');
    expect(AuditLog::where('action', 'category.created')->count())->toBe(1);
});

it('refuses category names that would clash with system pages or existing sections', function () {
    $admin = User::factory()->admin()->create();
    Category::factory()->create(['name' => 'News', 'slug' => 'news']);

    $this->actingAs($admin)->post('/admin/categories', newCategory(['name' => 'Admin']))->assertSessionHasErrors('name');
    $this->actingAs($admin)->post('/admin/categories', newCategory(['name' => 'About']))->assertSessionHasErrors('name');
    $this->actingAs($admin)->post('/admin/categories', newCategory(['name' => 'News!']))->assertSessionHasErrors('name');
    expect(Category::count())->toBe(1);
});

it('removes an inactive or hidden category from the public menu', function () {
    Category::factory()->create(['name' => 'Visible', 'slug' => 'visible']);
    Category::factory()->create(['name' => 'Off', 'slug' => 'off', 'is_active' => false]);
    Category::factory()->create(['name' => 'Unlisted', 'slug' => 'unlisted', 'show_in_nav' => false]);

    $this->get('/')->assertInertia(fn (Assert $page) => $page->has('nav', 1)->where('nav.0.slug', 'visible'));
});

// ---------- about + profiles ----------

it('shows only approved profiles on the public About page, and keeps former members separate', function () {
    StaffProfile::create(['name' => 'Shown Person', 'position' => 'Head, News', 'sort_order' => 1])->forceFill(['is_public' => true])->save();
    StaffProfile::create(['name' => 'Private Person', 'position' => 'Head, Sports', 'sort_order' => 2]);
    StaffProfile::create(['name' => 'Former Head', 'position' => 'Head, Features', 'sort_order' => 3])->forceFill(['is_public' => true, 'archived_at' => now()])->save();
    StaffProfile::create(['name' => 'Hidden Former', 'position' => 'Head, Literary', 'sort_order' => 4])->forceFill(['archived_at' => now()])->save();

    $this->get('/about')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('site/about')
        ->has('current', 1)->where('current.0.name', 'Shown Person')
        ->has('former', 1)->where('former.0.name', 'Former Head')
        ->missing('current.0.email'));
});

it('lets an admin manage profiles, nobody else', function () {
    $payload = ['name' => 'Juan Dela Cruz', 'position' => 'Head, News', 'category_id' => null, 'bio' => 'Writes.', 'link_url' => null,
        'sort_order' => 1, 'is_public' => true, 'archived' => false];

    $this->actingAs(User::factory()->head()->create())->post('/admin/profiles', $payload)->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->post('/admin/profiles', $payload)->assertRedirect('/admin/profiles');

    expect(StaffProfile::firstOrFail()->is_public)->toBeTrue();
});

it('rejects unsafe profile links', function () {
    $this->actingAs(User::factory()->admin()->create())->post('/admin/profiles', [
        'name' => 'X', 'position' => 'Y', 'link_url' => 'javascript:alert(1)', 'sort_order' => 0, 'is_public' => true, 'archived' => false,
    ])->assertSessionHasErrors('link_url');
});

// ---------- settings ----------

it('lets an admin edit public site text and shows it on the About and Contact pages', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs(User::factory()->editor()->create())->put('/admin/settings', ['contact_email' => 'x@example.com'])->assertForbidden();
    $this->actingAs($admin)->put('/admin/settings', ['contact_email' => 'press@example.com', 'about_mission' => 'Seek the truth.', 'facebook_url' => 'https://facebook.com/lossuenos'])->assertRedirect();

    expect(Setting::allValues()['contact_email'])->toBe('press@example.com');
    $this->get('/about')->assertInertia(fn (Assert $page) => $page->where('content.about_mission', 'Seek the truth.'));
    $this->get('/contact')->assertInertia(fn (Assert $page) => $page->where('info.contact_email', 'press@example.com'));
});

it('rejects invalid settings values', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put('/admin/settings', ['contact_email' => 'not-an-email', 'facebook_url' => 'javascript:alert(1)'])
        ->assertSessionHasErrors(['contact_email', 'facebook_url']);
});

// ---------- contact ----------

it('stores a valid contact message without keeping the raw IP address', function () {
    $this->post('/contact', contactPayload())->assertRedirect()->assertSessionHas('success');

    $row = ContactSubmission::firstOrFail();
    expect($row->email)->toBe('ana@example.com')->and($row->status)->toBe('new')
        ->and($row->ip_hash)->toHaveLength(64)->and($row->ip_hash)->not->toContain('127.0.0.1');
});

it('validates the contact form and requires privacy consent', function () {
    $this->post('/contact', contactPayload(['email' => 'nope', 'message' => 'short', 'consent' => false]))
        ->assertSessionHasErrors(['email', 'message', 'consent']);
    $this->post('/contact', contactPayload(['type' => 'hack']))->assertSessionHasErrors('type');
    expect(ContactSubmission::count())->toBe(0);
});

it('quietly drops spam that fills the hidden field', function () {
    $this->post('/contact', contactPayload(['website' => 'http://spam.example']))->assertRedirect()->assertSessionHas('success');

    expect(ContactSubmission::count())->toBe(0);
});

it('rate limits the contact form', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/contact', contactPayload())->assertRedirect();
    }

    $this->post('/contact', contactPayload())->assertStatus(429);
    expect(ContactSubmission::count())->toBe(5);
});

it('keeps the inbox to admins and the editor-in-chief', function () {
    $item = ContactSubmission::create(['name' => 'A', 'email' => 'a@example.com', 'type' => 'general', 'message' => 'A message long enough.']);

    $this->get('/inbox')->assertRedirect('/login');
    $this->actingAs(User::factory()->head()->create())->get('/inbox')->assertForbidden();
    $this->actingAs(User::factory()->head()->create())->get("/inbox/{$item->id}")->assertForbidden();
    $this->actingAs(User::factory()->editor()->create())->get('/inbox')->assertOk();
    $this->actingAs(User::factory()->admin()->create())->get("/inbox/{$item->id}")->assertOk();
});

it('starts the retention clock when an inquiry is resolved, and purges after a week', function () {
    $staff = User::factory()->editor()->create();
    $old = ContactSubmission::create(['name' => 'Old', 'email' => 'o@example.com', 'type' => 'general', 'message' => 'An old resolved message.']);
    $recent = ContactSubmission::create(['name' => 'New', 'email' => 'n@example.com', 'type' => 'general', 'message' => 'A recent resolved message.']);
    $open = ContactSubmission::create(['name' => 'Open', 'email' => 'p@example.com', 'type' => 'general', 'message' => 'Still unresolved message.']);

    $this->actingAs($staff)->put("/inbox/{$old->id}", ['status' => 'resolved'])->assertRedirect();
    $this->actingAs($staff)->put("/inbox/{$recent->id}", ['status' => 'resolved'])->assertRedirect();
    expect($old->fresh()->closed_at)->not->toBeNull();

    $old->forceFill(['closed_at' => now()->subDays(8)])->save();

    $this->artisan('contacts:purge')->assertSuccessful();

    expect(ContactSubmission::whereKey($old->id)->exists())->toBeFalse()
        ->and(ContactSubmission::whereKey($recent->id)->exists())->toBeTrue()
        ->and(ContactSubmission::whereKey($open->id)->exists())->toBeTrue();
});
