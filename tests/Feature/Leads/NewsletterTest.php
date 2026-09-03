<?php

use App\Modules\Leads\Livewire\NewsletterSignup;
use App\Modules\Leads\Mail\NewsletterConfirmMail;
use App\Modules\Leads\Models\NewsletterSubscriber;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
    Mail::fake();
});

it('double-opts-in: subscribe → pending + confirm email → confirm → marketable', function (): void {
    $component = Livewire::test(NewsletterSignup::class);
    $component->set('openedAt', microtime(true) - 10);
    $component->set('email', 'reader@example.com');
    $component->call('submitNewsletter');

    expect($component->get('subscribed'))->toBeTrue();

    $subscriber = NewsletterSubscriber::query()->sole();
    expect($subscriber->status->value)->toBe('pending')
        ->and($subscriber->confirmed_at)->toBeNull();

    Mail::assertSent(NewsletterConfirmMail::class, 1);

    // Double opt-in click (raw literal text in the view — no escaping).
    $this->get($subscriber->confirmUrl())->assertOk()->assertSee("You're subscribed", false);

    expect($subscriber->refresh()->status->value)->toBe('confirmed')
        ->and($subscriber->confirmed_at)->not->toBeNull();
});

it('is idempotent at the store level: one row per email, one confirm mail', function (): void {
    $component = Livewire::test(NewsletterSignup::class);
    $component->set('openedAt', microtime(true) - 10);
    $component->set('email', 'same@example.com');
    $component->call('submitNewsletter');

    $second = Livewire::test(NewsletterSignup::class);
    $second->set('openedAt', microtime(true) - 10);
    $second->set('email', 'same@example.com');
    $second->call('submitNewsletter');

    expect(NewsletterSubscriber::query()->count())->toBe(1);
});

it('confirms only once — a replayed confirm link changes nothing', function (): void {
    $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

    $this->get($subscriber->confirmUrl())->assertOk()->assertSee('Nothing to confirm');

    expect($subscriber->refresh()->status->value)->toBe('confirmed');
});

it('honours one-click unsubscribe from any state', function (): void {
    $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

    $this->get($subscriber->unsubscribeUrl())->assertOk()->assertSee("You're unsubscribed", false);

    expect($subscriber->refresh()->status->value)->toBe('unsubscribed')
        ->and($subscriber->unsubscribed_at)->not->toBeNull();

    // A stale confirm link after unsubscribe stays inert.
    $this->get($subscriber->confirmUrl())->assertOk()->assertSee('no longer active');
});

it('refuses unknown tokens with an honest page', function (): void {
    $this->get('/newsletter/confirm/not-a-real-token')->assertOk()->assertSee('expired');
    $this->get('/newsletter/unsubscribe/another-fake')->assertOk()->assertSee('expired');
});
