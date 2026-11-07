<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('issues a token that authenticates API requests', function () {
    $user = User::factory()->create();

    $token = $this->postJson('/api/v1/tokens', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'cli',
    ])->assertCreated()->json('token');

    $this->withToken($token)
        ->getJson('/api/v1/user')
        ->assertOk()
        ->assertExactJson(['data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]]);
});

it('refuses a token for a wrong password', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/tokens', [
        'email' => $user->email,
        'password' => 'wrong',
        'device_name' => 'cli',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');

    expect(PersonalAccessToken::count())->toBe(0);
});

it('requires a device name', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/tokens', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertUnprocessable()->assertJsonValidationErrors('device_name');
});

it('rejects unauthenticated API requests with JSON', function () {
    $this->getJson('/api/v1/user')->assertUnauthorized();
    $this->get('/api/v1/user')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
});

it('revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('cli')->plainTextToken;

    $this->withToken($token)->deleteJson('/api/v1/tokens/current')->assertNoContent();

    expect(PersonalAccessToken::count())->toBe(0);
});

it('throttles token requests after repeated failures', function () {
    $user = User::factory()->create();
    $attempt = fn () => $this->postJson('/api/v1/tokens', [
        'email' => $user->email,
        'password' => 'wrong',
        'device_name' => 'cli',
    ]);

    foreach (range(1, 5) as $i) {
        $attempt()->assertUnprocessable();
    }

    $attempt()->assertTooManyRequests();
});
