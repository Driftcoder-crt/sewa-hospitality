<?php

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it('renders the login screen', function (): void {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Sign in');
});

it('never reveals whether an email exists on failed login', function (): void {
    // 05-technical-specs 05-security-reliability §1.1 "Login UX": identical
    // messages for wrong-password and unknown-email — no account enumeration.
    $known = User::create([
        'name' => 'Existing Staff',
        'email' => 'staff@sewahospitality.com',
        'password' => 'correct-horse-battery-staple',
        'locale' => 'en',
        'status' => UserStatus::Active,
    ]);

    $unknownResponse = $this->from(route('login'))->post(route('login'), [
        'email' => 'ghost@sewahospitality.com',
        'password' => 'not-the-password',
    ]);

    $unknownResponse->assertRedirect(route('login'))->assertSessionHasErrors('email');
    $unknownMessage = session('errors')->first('email');

    $knownResponse = $this->from(route('login'))->post(route('login'), [
        'email' => $known->email,
        'password' => 'not-the-password',
    ]);

    $knownResponse->assertRedirect(route('login'))->assertSessionHasErrors('email');
    $knownMessage = session('errors')->first('email');

    expect($unknownMessage)->toBeString()->not->toBeEmpty()
        ->and($knownMessage)->toBe($unknownMessage)
        ->and($unknownMessage)->not->toContain('ghost@sewahospitality.com')
        ->and($unknownMessage)->not->toContain($known->email);
});

it('locks the login form after five failed attempts', function (): void {
    $user = User::create([
        'name' => 'Throttled Staff',
        'email' => 'throttled@sewahospitality.com',
        'password' => 'correct-horse-battery-staple',
        'locale' => 'en',
        'status' => UserStatus::Active,
    ]);

    // Fresh limiter state for this email + the test client IP (127.0.0.1).
    RateLimiter::clear(strtolower($user->email).'|127.0.0.1');

    $credentials = ['email' => $user->email, 'password' => 'not-the-password'];

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('login'), $credentials)->assertStatus(422);
    }

    $this->postJson(route('login'), $credentials)->assertStatus(429);
});
