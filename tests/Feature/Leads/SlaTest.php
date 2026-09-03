<?php

use App\Models\User;
use App\Modules\Leads\Enums\LeadEventType;
use App\Modules\Leads\Mail\OpsAlertMail;
use App\Modules\Leads\Models\Lead;
use App\Modules\Leads\Models\LeadEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Mail::fake();
});

it('fires the SLA breach once per lead with one ops alert (simulated)', function (): void {
    $lead = Lead::factory()->breached()->create();

    $this->artisan('sla:calculate')->assertSuccessful();

    $lead->refresh();
    $breaches = $lead->events()
        ->where('type', LeadEventType::System->value)
        ->where('payload->kind', 'sla_breached')
        ->get();

    expect($breaches)->toHaveCount(1);

    // Cron re-fires hourly — idempotency must hold.
    $this->artisan('sla:calculate')->assertSuccessful();
    expect($lead->events()
        ->where('type', LeadEventType::System->value)
        ->where('payload->kind', 'sla_breached')
        ->count())->toBe(1);

    Mail::assertSent(OpsAlertMail::class, 1);
});

it('does not breach leads that already responded', function (): void {
    Lead::factory()->breached()->create([
        'first_response_at' => now()->subMinutes(30),
    ]);

    $this->artisan('sla:calculate')->assertSuccessful();

    Mail::assertNothingSent();
});

it('escalates unassigned leads after the threshold, once', function (): void {
    $lead = Lead::factory()->create([
        'assigned_user_id' => null,
        'created_at' => now()->subHour(),
    ]);

    $this->artisan('sla:calculate')->assertSuccessful();

    expect($lead->events()
        ->where('payload->kind', 'escalated')
        ->count())->toBe(1);

    $this->artisan('sla:calculate')->assertSuccessful();

    expect($lead->refresh()->events()
        ->where('payload->kind', 'escalated')
        ->count())->toBe(1);
});

it('leaves fresh assigned leads alone', function (): void {
    $user = User::factory()->create();

    Lead::factory()->create([
        'assigned_user_id' => $user->id,
        'created_at' => now()->subMinutes(5),
    ]);

    $this->artisan('sla:calculate')->assertSuccessful();

    expect(LeadEvent::query()->where('type', LeadEventType::System->value)->count())->toBe(0);
});
