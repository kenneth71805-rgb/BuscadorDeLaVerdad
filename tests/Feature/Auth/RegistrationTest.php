<?php

test('public registration no longer exists', function () {
    $this->get('/register')->assertNotFound();

    $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password-12345',
        'password_confirmation' => 'password-12345',
    ])->assertStatus(404);

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});
