<?php

namespace App\Modules\Careers\Livewire;

use App\Modules\Careers\Enums\ApplicationStatus;
use App\Modules\Careers\Events\ApplicationReceived;
use App\Modules\Careers\Models\JobApplication;
use App\Modules\Careers\Models\JobPosting;
use App\Modules\Leads\Services\FormGuard;
use App\Support\Locks\Exceptions\IdempotencyConflictException;
use App\Support\Locks\IdempotencyStore;
use App\Support\Media\MediaUploadRules;
use App\Support\Security\TurnstileVerifier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Application form (06-hr doc §3/§5): resume ≤5MB pdf/doc/docx stored
 * on the PRIVATE local disk (PII never public), idempotency key against
 * double-apply, consent logged with policy version, Turnstile +
 * honeypot + rate limits, resumable-friendly validation (typed data
 * never lost — the reference's candidate-UX defect eliminated).
 *
 * Embedded island inside the public job page — NO layout attribute.
 */
class ApplicationForm extends Component
{
    use WithFileUploads;

    public JobPosting $posting;

    public string $applicantName = '';

    public string $applicantEmail = '';

    public string $applicantPhone = '';

    public string $coverMessage = '';

    public bool $consent = false;

    /** Resume upload (Livewire temp file). */
    #[Validate]
    public $resume;

    public string $websiteUrl = '';

    public string $turnstileToken = '';

    public string $idempotencyKey = '';

    public float $openedAt = 0;

    public string $status = 'idle';

    public function mount(JobPosting $posting): void
    {
        $this->posting = $posting;
        $this->idempotencyKey = (string) Str::ulid();
        $this->openedAt = microtime(true);
    }

    protected function rules(): array
    {
        return array_merge([
            'applicantName' => ['required', 'string', 'max:120'],
            'applicantEmail' => ['required', 'email:filter', 'max:190'],
            'applicantPhone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'coverMessage' => ['required', 'string', 'min:20', 'max:3000'],
            'consent' => ['accepted'],
            // Resume: PDF/DOC/DOCX ≤ 5 MB — careers only (MediaUploadRules).
            'resume' => MediaUploadRules::resumeRules()['file'],
        ]);
    }

    public function updatedResume(): void
    {
        // Friendly early feedback: validate ONLY the file so a bad resume
        // is rejected before the candidate types anything else.
        $this->validateOnly('resume', ['resume' => MediaUploadRules::resumeRules()['file']]);
    }

    public function submitApplication(): void
    {
        if ($this->status === 'success') {
            return;
        }

        // Spam guard (fake success for bots — learn nothing).
        if (! FormGuard::human($this->websiteUrl, $this->openedAt)) {
            Log::channel('ops')->warning('Application form: honeypot tripped', ['job' => $this->posting->slug]);
            $this->status = 'success';

            return;
        }

        if (! FormGuard::allowed('application')) {
            $this->addError('form', 'Too many attempts — please wait a minute.');

            return;
        }

        if (! TurnstileVerifier::verify($this->turnstileToken, request()->ip())) {
            $this->addError('form', 'Human verification failed — please retry.');

            return;
        }

        $this->validate();

        try {
            $result = IdempotencyStore::remember(
                key: $this->idempotencyKey,
                requestFingerprint: sha1(static::class.'|'.$this->posting->getKey().'|'.$this->applicantEmail.'|'.$this->applicantName),
                task: fn (): array => $this->store(),
            );
        } catch (IdempotencyConflictException) {
            $this->addError('form', 'This application is already with us — no need to apply twice.');

            return;
        } catch (\Throwable $e) {
            report($e);
            $this->addError('form', 'We could not save your application — please retry in a moment.');

            return;
        }

        $this->status = 'success';
        $this->handleSuccess($result['result'], $result['replayed']);
    }

    /** Transactional write: private resume storage + application row. */
    private function store(): array
    {
        $path = Storage::disk(JobApplication::RESUME_DISK)->putFile(
            JobApplication::RESUME_DIR,
            $this->resume,
        );

        if (! $path) {
            throw ValidationException::withMessages(['resume' => 'We could not store the resume — please retry.']);
        }

        $application = DB::transaction(function () use ($path): JobApplication {
            $application = JobApplication::query()->create([
                'job_posting_id' => $this->posting->getKey(),
                'applicant_name' => $this->applicantName,
                'applicant_email' => mb_strtolower(trim($this->applicantEmail)),
                'applicant_phone' => $this->applicantPhone,
                'resume_path' => $path,
                'cover_message' => $this->coverMessage,
                'source' => 'site',
                'source_detail' => $this->posting->publicPath(),
                'status' => ApplicationStatus::New,
                'idempotency_key' => $this->idempotencyKey,
                'consent_at' => now(),
                'consent_version' => (string) config('sewa.privacy_version'),
            ]);

            $application->forceFill(['notes' => [['type' => 'form', 'at' => now()->toIso8601String(), 'note' => 'Applied via '.$this->posting->publicPath()]]])->save();

            return $application;
        });

        ApplicationReceived::dispatch($application);

        return ['id' => $application->getKey()];
    }

    private function handleSuccess(array $result, bool $replayed): void
    {
        $this->redirect(route('thank-you', ['source' => 'contact', 'ref' => $result['id']]), navigate: true);
    }

    public function render(): View
    {
        return view('careers.livewire.application-form');
    }
}
