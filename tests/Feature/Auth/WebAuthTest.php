<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

// Vue pages arrive with the frontend scaffold, so components are asserted without checking the files exist.

it('redirects guests from the timeline to the login page', function () {
    $this->get('/')->assertRedirect('/login');
});

it('renders the login page through Inertia', function () {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/login', false));
});

it('renders the timeline for a signed-in user', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('timeline/index', false));
});

it('logs in with valid credentials and lands on the timeline', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('rejects an invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('logs out', function () {
    $this->actingAs(User::factory()->create())
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});

it('does not offer public registration', function () {
    $this->get('/register')->assertNotFound();
});

it('emails a password reset link', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])->assertSessionHasNoErrors();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('passes the reset token and email to the reset password page', function () {
    $this->get('/reset-password/abc123?email=ops@example.com')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/reset-password', false)
            ->where('token', 'abc123')
            ->where('email', 'ops@example.com'));
});
