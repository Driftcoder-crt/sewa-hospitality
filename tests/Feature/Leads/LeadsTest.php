<?php

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Leads\Livewire\ContactForm;
use App\Modules\Leads\Livewire\LeadDetail;
use App\Modules\Leads\Livewire\LeadsInbox;
use App\Modules\Leads\Models\Lead;
use App\Modules\Leads\Services\LeadStatusMachine;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
    Mail::fake();
});

/** Age the form past the time-trap and fill the contact fields. */
function fillContact($component, array $overrides = []): void
{
    $component->set('openedAt', microtime(true) - 10);

    $component->set('form.name', $overrides['name'] ?? 'Rohit Sharma');
    $component->set('form.email', $overrides['email'] ?? 'rohit@example.com');
    $component->set('form.phone', $overrides['phone'] ?? '+91 98732 55531');
    $component->set('form.message', $overrides['message'] ?? 'We are a team of 12 moving to Gurugram in March and need corporate housing.');
    $component->set('form.consent', $overrides['consent'] ?? true);
}

it('records a contact lead through the form island and redirects to the honest thank-you', function (): void {
    $component = Livewire::test(ContactForm::class);
    fillContact($component);

    $component->call('submitLead')->assertRedirect(route('thank-you', ['source' => 'contact', 'ref' => Lead::query()->sole()->getKey()], false));

    expect(Lead::query()->count())->toBe(1);

    $lead = Lead::query()->sole();
    expect($lead->type->value)->toBe('enquiry')
        ->and($lead->status->value)->toBe('new')
        ->and($lead->consent_version)->toBe(config('sewa.privacy_version'))
        ->and($lead->sla_due_at)->not->toBeNull()
        ->and($lead->events()->count())->toBeGreaterThanOrEqual(1);
});

it('collapses a double submit into ONE lead via the idempotency key', function (): void {
    $component = Livewire::test(ContactForm::class);
    fillContact($component);

    $component->call('submitLead')->assertRedirect();
    $component->call('submitLead');

    expect(Lead::query()->count())->toBe(1);
});

it('rate limits the sixth submit within a minute (error lock #3)', function (): void {
    for ($i = 0; $i < 5; $i++) {
        $component = Livewire::test(ContactForm::class);
        fillContact($component, ['email' => "user-{$i}@example.com"]);
        $component->call('submitLead');
    }

    $sixth = Livewire::test(ContactForm::class);
    fillContact($sixth, ['email' => 'user-6@example.com']);

    $sixth->call('submitLead')->assertHasErrors(['form']);

    expect(Lead::query()->count())->toBe(5);
});

it('fails closed when Turnstile answers success=false', function (): void {
    config([
        'sewa.turnstile.secret' => 'test-secret',
        'sewa.turnstile.verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
    ]);
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => false], 200),
    ]);

    $component = Livewire::test(ContactForm::class);
    fillContact($component);
    $component->set('turnstileToken', 'bad-token');

    $component->call('submitLead')->assertHasErrors(['form']);

    expect(Lead::query()->count())->toBe(0);
});

it('silently fake-succeeds a honeypot bot without writing anything', function (): void {
    $component = Livewire::test(ContactForm::class);
    fillContact($component);
    $component->set('websiteUrl', 'http://spam.example');

    $component->call('submitLead');

    expect(Lead::query()->count())->toBe(0);
});

it('rejects illegal pipeline transitions and requires a deal reference for won', function (): void {
    // Machine rules.
    expect(fn () => LeadStatusMachine::assertTransition(
        LeadStatus::Won,
        LeadStatus::Contacted,
    ))->toThrow(InvalidArgumentException::class);

    $lead = Lead::factory()->create();

    // LeadDetail authorizes on mount — drive it as a full-lead role.
    $admin = User::factory()->create();
    $admin->syncRoles(['admin']);

    $component = Livewire::actingAs($admin)->test(LeadDetail::class, ['lead' => $lead]);
    $component->set('status', 'won')->call('changeStatus')->assertHasErrors(['status']);
    expect($lead->refresh()->status->value)->toBe('new');

    $component->set('dealReference', 'SEWA-Q-2026-0001')->call('changeStatus')->assertHasNoErrors();
    expect($lead->refresh()->status->value)->toBe('won')
        ->and($lead->refresh()->enrichment['deal_reference'])->toBe('SEWA-Q-2026-0001');
});

it('stamps first_response_at on the first move off new', function (): void {
    $lead = Lead::factory()->create(['first_response_at' => null]);

    $admin = User::factory()->create();
    $admin->syncRoles(['admin']);

    $component = Livewire::actingAs($admin)->test(LeadDetail::class, ['lead' => $lead]);
    $component->set('status', 'contacted')->call('changeStatus');

    expect($lead->refresh()->first_response_at)->not->toBeNull();
});

it('gates PII behind leads.pii.view (consultant sees redacted)', function (): void {
    $consultant = User::factory()->create();
    $consultant->syncRoles(['consultant']);

    $lead = Lead::factory()->create(['assigned_user_id' => $consultant->id]);

    // Consultant (leads.view but NOT leads.pii.view) drives the screen.
    $component = Livewire::actingAs($consultant)->test(LeadDetail::class, ['lead' => $lead]);
    $component->assertDontSee($lead->email)
        ->assertSee('pii.view required');
});

it('permission-gates the CSV export and audits it', function (): void {
    Lead::factory()->count(3)->create();

    $consultant = User::factory()->create();
    $consultant->syncRoles(['consultant']);

    Livewire::actingAs($consultant)
        ->test(LeadsInbox::class)
        ->call('exportCsv')
        ->assertForbidden();

    $admin = User::factory()->create();
    $admin->syncRoles(['admin']);

    Livewire::actingAs($admin)
        ->test(LeadsInbox::class)
        ->call('exportCsv')
        ->assertFileDownloaded();

    expect(ActivityLog::query()->where('action', 'export')->exists())->toBeTrue();
});
