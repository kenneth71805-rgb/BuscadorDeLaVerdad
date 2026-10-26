<?php

test('public registration no longer exists', function () {
    $this->get('/register')->assertNotFound();

    // POST may be answered 404 or 405 (the public catch-all route only accepts GET); either way, nothing is created.
    $status = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password-12345',
        'password_confirmation' => 'password-12345',
    ])->getStatusCode();

    expect($status)->toBeIn([404, 405]);
    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
});
