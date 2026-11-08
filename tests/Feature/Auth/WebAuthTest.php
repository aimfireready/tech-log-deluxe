<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('redirects guests from the timeline to the login page', function () {
    $this->get('/')->assertRedirect('/login');
});

it('renders the login page through Inertia', function () {
    $this->get('/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/login'));
});

it('renders the timeline for a signed-in user', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('timeline/index'));
});

it('shares only safe user fields with every page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user', ['id' => $user->id, 'name' => $user->name, 'email' => $user->email])
            ->where('flash.status', null));
});

it('shows the reset-link status on the forgot password page', function () {
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    $this->get('/forgot-password')
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/forgot-password')
            ->where('flash.status', __('passwords.sent')));
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
            ->component('auth/reset-password')
            ->where('token', 'abc123')
            ->where('email', 'ops@example.com'));
});
