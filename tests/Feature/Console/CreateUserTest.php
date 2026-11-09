<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates a user who can log in', function () {
    $this->artisan('user:create')
        ->expectsQuestion('Name', 'Ops Admin')
        ->expectsQuestion('Email', 'ops@example.com')
        ->expectsQuestion('Password', 'a-long-secret')
        ->expectsOutput('Created ops@example.com.')
        ->assertSuccessful();

    $user = User::where('email', 'ops@example.com')->sole();
    expect($user->name)->toBe('Ops Admin')
        ->and(Hash::check('a-long-secret', $user->password))->toBeTrue();
});

it('refuses a duplicate email', function () {
    User::factory()->create(['email' => 'ops@example.com']);

    $this->artisan('user:create')
        ->expectsQuestion('Name', 'Ops Admin')
        ->expectsQuestion('Email', 'ops@example.com')
        ->expectsQuestion('Password', 'a-long-secret')
        ->expectsOutput('The email has already been taken.')
        ->assertFailed();

    expect(User::count())->toBe(1);
});

it('refuses an invalid email', function () {
    $this->artisan('user:create')
        ->expectsQuestion('Name', 'Ops Admin')
        ->expectsQuestion('Email', 'not-an-email')
        ->expectsQuestion('Password', 'a-long-secret')
        ->assertFailed();

    expect(User::count())->toBe(0);
});
