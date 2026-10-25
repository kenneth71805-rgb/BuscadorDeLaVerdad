<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/admin')->assertRedirect('/login');
    $this->get('/editor')->assertRedirect('/login');
    $this->get('/category')->assertRedirect('/login');
});

test('each role is sent to its own dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/dashboard')->assertRedirect('/admin');
    $this->actingAs(User::factory()->editor()->create())->get('/dashboard')->assertRedirect('/editor');
    $this->actingAs(User::factory()->head()->create())->get('/dashboard')->assertRedirect('/category');
});

test('an account without a staff role has no dashboard', function () {
    $this->actingAs(User::factory()->create())->get('/dashboard')->assertForbidden();
});
