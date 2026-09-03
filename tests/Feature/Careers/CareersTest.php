<?php

use App\Models\User;
use App\Modules\Careers\Enums\ApplicationStatus;
use App\Modules\Careers\Livewire\ApplicationForm;
use App\Modules\Careers\Livewire\ApplicationsPipeline;
use App\Modules\Careers\Mail\ApplicationAckMail;
use App\Modules\Careers\Mail\ApplicationReceivedMail;
use App\Modules\Careers\Mail\ApplicationStatusMail;
use App\Modules\Careers\Models\Employee;
use App\Modules\Careers\Models\JobApplication;
use App\Modules\Careers\Models\JobPosting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
    Mail::fake();
});

/** Fill the application form past the time-trap. */
function fillApplication($component, array $overrides = []): void
{
    $component->set('openedAt', microtime(true) - 10);
    $component->set('applicantName', $overrides['name'] ?? 'Aisha Verma');
    $component->set('applicantEmail', $overrides['email'] ?? 'aisha@example.com');
    $component->set('applicantPhone', $overrides['phone'] ?? '+91 98111 22233');
    $component->set('coverMessage', $overrides['message'] ?? 'Five years of client-facing relocation work, fluent in three languages, obsessed with the details.');
    $component->set('consent', true);
    $component->set('resume', $overrides['resume'] ?? UploadedFile::fake()->create('resume.pdf', 200, 'application/pdf'));
}

it('renders open, paused and closed job pages — never a 404 — and drafts stay invisible', function (): void {
    $open = JobPosting::factory()->create();
    $paused = JobPosting::factory()->paused()->create();
    $closed = JobPosting::factory()->closed()->create();
    $draft = JobPosting::factory()->draft()->create();

    $this->get('/careers')->assertOk()->assertSee($open->title)->assertSee($paused->title);

    $this->get($open->publicPath())->assertOk()->assertSee('Apply for this role');
    $this->get($paused->publicPath())->assertOk()->assertSee('Applications paused');
    $this->get($closed->publicPath())->assertOk()->assertSee('Applications closed')->assertSee('Similar openings');
    $this->get($draft->publicPath())->assertNotFound();
});

it('accepts an application: private resume, consent version, ack + received emails', function (): void {
    $posting = JobPosting::factory()->create();

    $component = Livewire::test(ApplicationForm::class, ['posting' => $posting]);
    fillApplication($component);
    $component->call('submitApplication')->assertRedirect();

    $application = JobApplication::query()->sole();
    expect($application->status->value)->toBe('new')
        ->and($application->consent_version)->toBe(config('sewa.privacy_version'))
        ->and($application->resume_path)->not->toBeNull();

    Storage::disk('local')->assertExists($application->resume_path);

    // The resume never lands on the public media disk.
    expect(str_starts_with($application->resume_path, 'resumes/'))->toBeTrue();

    Mail::assertSent(ApplicationAckMail::class, 1);
    Mail::assertSent(ApplicationReceivedMail::class, 1);
});

it('blocks a double-apply via the idempotency key', function (): void {
    $posting = JobPosting::factory()->create();

    $component = Livewire::test(ApplicationForm::class, ['posting' => $posting]);
    fillApplication($component);
    $component->call('submitApplication');

    $component->call('submitApplication');

    expect(JobApplication::query()->count())->toBe(1);
});

it('rejects a corrupt resume type with friendly guidance and keeps typed data', function (): void {
    $posting = JobPosting::factory()->create();

    $component = Livewire::test(ApplicationForm::class, ['posting' => $posting]);
    fillApplication($component, ['resume' => UploadedFile::fake()->create('malware.exe', 100)]);
    $component->call('submitApplication')->assertHasErrors(['resume']);

    expect(JobApplication::query()->count())->toBe(0)
        ->and($component->get('applicantName'))->toBe('Aisha Verma'); // typed data preserved
});

it('rejects an oversized resume (> 5 MB)', function (): void {
    $posting = JobPosting::factory()->create();

    $component = Livewire::test(ApplicationForm::class, ['posting' => $posting]);
    fillApplication($component, ['resume' => UploadedFile::fake()->create('resume.pdf', 6 * 1024)]);
    $component->call('submitApplication')->assertHasErrors(['resume']);

    expect(JobApplication::query()->count())->toBe(0);
});

it('drives the ATS status machine and emails catalog stages', function (): void {
    $application = JobApplication::factory()->create();

    // Terminal lock.
    $application->forceFill(['status' => ApplicationStatus::Hired])->save();

    $hr = User::factory()->create();
    $hr->syncRoles(['hr-manager']);

    $this->actingAs($hr);
    Livewire::test(ApplicationsPipeline::class)
        ->call('moveApplication', $application->getKey(), 'screening')
        ->assertHasErrors(['pipeline']);

    // Fresh application moves to interview → status email fires.
    $fresh = JobApplication::factory()->create();

    Livewire::test(ApplicationsPipeline::class)
        ->call('moveApplication', $fresh->getKey(), 'interview')
        ->assertHasNoErrors();

    expect($fresh->refresh()->status->value)->toBe('interview');

    Mail::assertSent(ApplicationStatusMail::class, 1);
});

it('shows the team grid sources on /team/{person} only for public employees', function (): void {
    $public = Employee::factory()->create();
    $hidden = Employee::factory()->internal()->create();

    $this->get($public->publicPath())->assertOk()->assertSee($public->full_name);
    $this->get($hidden->publicPath())->assertNotFound();
});
