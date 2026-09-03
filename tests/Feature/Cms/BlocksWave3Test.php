<?php

use App\Modules\Careers\Models\Employee;
use App\Modules\Cms\Services\BlockRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed([RolesAndPermissionsSeeder::class]);
});

/** Render one block through the shared loop and return the HTML. */
function renderWave3Block(string $type, array $data): string
{
    return view('cms.partials.blocks', [
        'blocks' => [['type' => $type, 'data' => $data]],
        'leadIndex' => 0,
    ])->render();
}

it('has all ten wave-3 blocks registered', function (): void {
    foreach (['lead_form', 'offer_banner', 'newsletter_capture', 'promo_card_grid',
        'countdown_promo', 'exit_intent_modal', 'sticky_cta_bar',
        'trust_checklist', 'case_story', 'team_grid'] as $type) {
        expect(BlockRegistry::has($type))->toBeTrue("Missing wave-3 block [{$type}]");
    }
});

it('renders E2 lead form shell (islands are e2e-tested in LeadsTest)', function (): void {
    $html = renderWave3Block('lead_form', [
        'form_type' => 'contact', 'heading' => 'Talk to us',
        'benefits' => [['text' => 'Reply within 2 business hours']],
    ]);

    expect($html)->toContain('Talk to us')
        ->toContain('Reply within 2 business hours')
        ->and(BlockRegistry::component('lead_form'))->toBe('blocks.lead-form');
});

it('renders E3 offer banner with code chip and dismissal memory key', function (): void {
    $html = renderWave3Block('offer_banner', [
        'heading' => 'Fleet month', 'code' => 'SEWA-GREET', 'theme' => 'brand',
    ]);

    expect($html)->toContain('Fleet month')
        ->toContain('SEWA-GREET')
        ->toContain('sewa.offer.');
});

it('renders E5 promo card grid with badges and validity', function (): void {
    $html = renderWave3Block('promo_card_grid', [
        'columns' => '3',
        'items' => [['title' => 'Extended stay', 'badge' => 'Housing', 'terms' => '30+ nights', 'validity' => 'Rolling']],
    ]);

    expect($html)->toContain('Extended stay')
        ->toContain('Housing')
        ->toContain('Rolling');
});

it('renders E6 countdown promo live before deadline and gracefully expired after', function (): void {
    $live = renderWave3Block('countdown_promo', [
        'heading' => 'Clinic', 'deadline' => now()->addDays(10)->toIso8601String(),
    ]);
    expect($live)->toContain('Clinic')->toContain('Days');

    $expired = renderWave3Block('countdown_promo', [
        'heading' => 'Clinic', 'deadline' => now()->subDay()->toIso8601String(),
    ]);
    expect($expired)->toContain('offer window has closed')
        ->not->toContain('Days');
});

it('renders E7 exit-intent modal with the 1/7d frequency cap key', function (): void {
    $html = renderWave3Block('exit_intent_modal', [
        'heading' => 'Before you go', 'trigger' => 'exit', 'mode' => 'newsletter',
    ]);

    expect($html)->toContain('Before you go')
        ->toContain('sewa.exitcap.')
        ->toContain('at most once a week');
});

it('renders E8 sticky CTA bar with tel action and dismissal memory', function (): void {
    $html = renderWave3Block('sticky_cta_bar', [
        'items' => [['label' => 'Call now', 'url' => 'tel:+919873255531', 'icon' => 'call']],
    ]);

    expect($html)->toContain('tel:+919873255531')
        ->toContain('sewa.ctabar.');
});

it('renders D4 trust checklist with the verified standard link', function (): void {
    $html = renderWave3Block('trust_checklist', [
        'heading' => 'The standard', 'items' => [['text' => 'Physical inspection']],
    ]);

    expect($html)->toContain('Physical inspection')
        ->toContain('/housing/verified');
});

it('renders D5 case story with metrics', function (): void {
    $html = renderWave3Block('case_story', [
        'client_label' => 'A technology company',
        'challenge' => 'Forty engineers.', 'approach' => 'City leads.', 'outcome' => 'All settled.',
        'metrics' => [['value' => '40', 'label' => 'Moves']],
    ]);

    expect($html)->toContain('Forty engineers.')
        ->toContain('40')
        ->toContain('Moves');
});

it('renders D6 team grid from public employees with an honest zero-state', function (): void {
    $empty = renderWave3Block('team_grid', ['heading' => 'Team', 'department' => 'all']);
    expect($empty)->toContain('being prepared');

    Employee::factory()->create();
    $hidden = Employee::factory()->internal()->create();

    $html = renderWave3Block('team_grid', ['heading' => 'Team', 'department' => 'all', 'limit' => '8']);
    expect($html)->toContain(Employee::query()->where('is_public', true)->first()->full_name)
        ->not->toContain($hidden->full_name);
});
