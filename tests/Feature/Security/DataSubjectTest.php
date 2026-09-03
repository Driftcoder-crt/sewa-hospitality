<?php

use App\Models\User;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Careers\Models\JobApplication;
use App\Modules\Leads\Models\Lead;
use App\Modules\Leads\Models\NewsletterSubscriber;
use App\Support\Security\DataSubjectTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('exports everything the platform holds for one subject', function () {
    $lead = Lead::factory()->create(['email' => 'subject@example.com']);
    Lead::factory()->create(); // another subject — must not appear
    $application = JobApplication::factory()->create(['applicant_email' => 'subject@example.com']);
    NewsletterSubscriber::factory()->create(['email' => 'subject@example.com']);

    $payload = DataSubjectTool::export('Subject@Example.com'); // case-insensitive

    expect($payload['leads'])->toHaveCount(1)
        ->and($payload['job_applications'])->toHaveCount(1)
        ->and($payload['newsletter_subscriptions'])->toHaveCount(1)
        ->and($payload['leads'][0]['email'])->toBe($lead->email)
        ->and($payload['job_applications'][0]['applicant_email'])->toBe($application->applicant_email);
});

it('anonymizes the subject in place while keeping the pipeline history', function () {
    Storage::fake(JobApplication::RESUME_DISK);

    $lead = Lead::factory()->create(['email' => 'subject@example.com', 'phone' => '+91 9800000000']);
    $application = JobApplication::factory()->create([
        'applicant_email' => 'subject@example.com',
        'resume_path' => 'careers/resume.pdf',
    ]);
    Storage::disk(JobApplication::RESUME_DISK)->put('careers/resume.pdf', 'resume bytes');

    $counts = DataSubjectTool::anonymize('Subject@Example.com');

    expect($counts)->toBe(['leads' => 1, 'applications' => 1]);

    $lead->refresh();
    $application->refresh();

    expect($lead->name)->toBe('[erased]')
        ->and($lead->email)->not()->toBe('subject@example.com')
        ->and($lead->phone)->toBeNull()
        ->and($lead->message)->toBeNull()
        ->and($application->applicant_name)->toBe('[erased]')
        ->and($application->applicant_email)->not()->toBe('subject@example.com')
        // Resume bytes are gone (DPDP erasure).
        ->and(Storage::disk(JobApplication::RESUME_DISK)->exists('careers/resume.pdf'))->toBeFalse();
});

it('never touches financial records during erasure', function () {
    Lead::factory()->create(['email' => 'subject@example.com']);
    $invoice = Invoice::factory()->create();

    DataSubjectTool::anonymize('subject@example.com');

    $invoice->refresh();

    expect(Invoice::query()->whereKey($invoice->getKey())->exists())->toBeTrue();
});

it('is a no-op for an unknown subject', function () {
    Lead::factory()->count(2)->create();

    $counts = DataSubjectTool::anonymize('nobody@nowhere.example');

    expect($counts)->toBe(['leads' => 0, 'applications' => 0])
        ->and(Lead::query()->count())->toBe(2);
});

it('exports portal + audit sections for a staff user with that email', function () {
    $user = User::factory()->create(['email' => 'staff@sewahospitality.com']);

    $payload = DataSubjectTool::export('staff@sewahospitality.com');

    expect($payload['users'])->toHaveCount(1)
        ->and($payload['users'][0]['id'])->toBe($user->getKey())
        ->and($payload['portal_moves'])->toBe([])
        ->and($payload['audit_log'])->toBe([]);
});
