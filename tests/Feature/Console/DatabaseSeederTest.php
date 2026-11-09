<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('creates the test user in testing', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
});

it('never creates the test user in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(0);
});
