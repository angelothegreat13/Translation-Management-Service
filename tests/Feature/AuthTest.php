<?php

declare(strict_types=1);

use App\Models\User;

it('issues a token with valid credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('secret123')]);

    $response = $this->postJson('/api/v1/auth/token', [
        'email'    => $user->email,
        'password' => 'secret123',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token']);
});

it('returns 401 with invalid credentials', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->postJson('/api/v1/auth/token', [
        'email'    => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertUnauthorized()
        ->assertJson(['message' => 'Invalid credentials.']);
});

it('returns 422 when email is missing', function () {
    $this->postJson('/api/v1/auth/token', ['password' => 'secret123'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('returns 422 when password is missing', function () {
    $this->postJson('/api/v1/auth/token', ['email' => 'test@example.com'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('rejects unauthenticated requests to protected routes', function () {
    $this->getJson('/api/v1/translations')
        ->assertUnauthorized();
});

it('allows authenticated requests to protected routes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/translations')
        ->assertOk();
});
