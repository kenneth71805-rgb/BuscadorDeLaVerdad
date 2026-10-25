<?php

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// Page-render tests must not depend on a built asset manifest or a running SSR server.
beforeEach(function () {
    $this->withoutVite();
    config(['inertia.ssr.enabled' => false]);
});

function newStaffPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Maria Santos',
        'email' => 'maria@example.com',
        'role' => 'category_head',
        'position' => 'Sports Editor',
        'is_active' => true,
        'category_ids' => [],
        'password' => 'long-enough-pass-1',
    ], $overrides);
}

it('keeps each dashboard to its own role', function () {
    $admin = User::factory()->admin()->create();
    $editor = User::factory()->editor()->create();
    $head = User::factory()->head()->create();

    $this->actingAs($head)->get('/admin')->assertForbidden();
    $this->actingAs($head)->get('/editor')->assertForbidden();
    $this->actingAs($editor)->get('/admin')->assertForbidden();
    $this->actingAs($editor)->get('/admin/staff')->assertForbidden();
    $this->actingAs($admin)->get('/editor')->assertForbidden();
    $this->actingAs($admin)->get('/category')->assertForbidden();
});

it('shows real numbers on the admin dashboard', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get('/admin')->assertInertia(fn (Assert $page) => $page
        ->component('admin/dashboard')
        ->where('stats.published', 0)
        ->where('stats.staff', 1));
});

it('lists recent audit activity with the actor name, and System when there is none', function () {
    $admin = User::factory()->admin()->create();
    AuditLog::record('staff.created', $admin, [], $admin);
    AuditLog::record('article.status_changed');

    $this->actingAs($admin)->get('/admin')->assertInertia(fn (Assert $page) => $page
        ->component('admin/dashboard')
        ->has('recentActivity', 2)
        ->where('recentActivity.0.actor', 'System')
        ->where('recentActivity.1.actor', $admin->name));
});

it('lets an admin create a head of category with assigned categories, and logs it', function () {
    $admin = User::factory()->admin()->create();
    $cats = Category::factory()->count(2)->create();

    $this->actingAs($admin)->post('/admin/staff', newStaffPayload([
        'category_ids' => $cats->pluck('id')->all(),
        'can_publish' => true,
    ]))->assertRedirect('/admin/staff');

    $member = User::where('email', 'maria@example.com')->firstOrFail();
    expect($member->role)->toBe(Role::CategoryHead)
        ->and($member->categories()->count())->toBe(2)
        ->and($member->can_publish)->toBeTrue()
        ->and($member->password)->not->toBe('long-enough-pass-1');
    expect(AuditLog::where('action', 'staff.created')->count())->toBe(1);
});

it('refuses a head of category without any category, and weak passwords', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/staff', newStaffPayload())->assertSessionHasErrors('category_ids');
    $this->actingAs($admin)->post('/admin/staff', newStaffPayload(['role' => 'editor_in_chief', 'password' => 'short1']))
        ->assertSessionHasErrors('password');
    expect(User::where('email', 'maria@example.com')->exists())->toBeFalse();
});

it('does not let non-admins manage staff', function () {
    foreach ([User::factory()->editor()->create(), User::factory()->head()->create()] as $user) {
        $this->actingAs($user)->post('/admin/staff', newStaffPayload(['role' => 'editor_in_chief']))->assertForbidden();
    }
    expect(User::where('email', 'maria@example.com')->exists())->toBeFalse();
});

it('does not let an admin demote or deactivate themselves', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->create(); // a second admin exists

    $this->actingAs($admin)->put("/admin/staff/{$admin->id}", newStaffPayload([
        'name' => $admin->name, 'email' => $admin->email, 'role' => 'admin', 'is_active' => false, 'password' => null,
    ]))->assertSessionHasErrors('role');

    expect($admin->fresh()->is_active)->toBeTrue();
});

it('protects the last active admin', function () {
    $last = User::factory()->admin()->create();
    $other = User::factory()->editor()->create();
    // another admin acting would be needed to demote $last; with one admin the rule must still hold
    $actor = User::factory()->admin()->create();
    $actor->forceFill(['is_active' => true])->save();
    $last->forceFill(['is_active' => false])->save(); // now $actor is the only active admin

    $this->actingAs($actor)->put("/admin/staff/{$actor->id}", newStaffPayload([
        'name' => $actor->name, 'email' => $actor->email, 'role' => 'editor_in_chief', 'password' => null,
    ]))->assertSessionHasErrors('role');

    expect($actor->fresh()->role)->toBe(Role::Admin);
});

it('records role changes and deactivation in the audit log', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->editor()->create();

    $this->actingAs($admin)->put("/admin/staff/{$member->id}", newStaffPayload([
        'name' => $member->name, 'email' => $member->email, 'role' => 'editor_in_chief', 'is_active' => false, 'password' => null,
    ]))->assertRedirect('/admin/staff');

    $log = AuditLog::where('action', 'staff.updated')->firstOrFail();
    expect($log->meta['is_active'])->toBe(['from' => true, 'to' => false]);
    expect($member->fresh()->is_active)->toBeFalse();
});

it('does not let a deactivated account log in or keep a session', function () {
    $member = User::factory()->editor()->create();
    $member->forceFill(['is_active' => false])->save();

    $this->post('/login', ['email' => $member->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->actingAs($member)->get('/editor')->assertRedirect('/login');
    $this->assertGuest();
});

it('shows a head only their own categories on their dashboard', function () {
    $mine = Category::factory()->create();
    Category::factory()->create();
    $head = User::factory()->head()->create();
    $head->categories()->attach($mine);

    $this->actingAs($head)->get('/category')->assertInertia(fn (Assert $page) => $page
        ->component('category/dashboard')
        ->has('categories', 1)
        ->where('categories.0.id', $mine->id));
});

it('creates an admin from the command line with an interactively entered password', function () {
    $this->artisan('staff:create-admin', ['email' => 'chief@example.com', 'name' => 'Chief Admin'])
        ->expectsQuestion('Password (min 12 characters)', 'a-long-secret-123')
        ->assertSuccessful();

    $user = User::where('email', 'chief@example.com')->firstOrFail();
    expect($user->role)->toBe(Role::Admin)->and($user->can_override)->toBeFalse();
});
