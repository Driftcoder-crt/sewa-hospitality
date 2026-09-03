<?php

namespace App\Modules\Careers\Livewire;

use App\Modules\Careers\Enums\ApplicationStatus;
use App\Modules\Careers\Events\ApplicationStatusChanged;
use App\Modules\Careers\Models\JobApplication;
use App\Modules\Careers\Models\JobPosting;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * ATS pipeline (06-hr §4.2): new/screening/shortlisted/interview/offer/
 * hired/rejected/withdrawn kanban — drag or select, resume preview via
 * signed URL, notes log, rating, duplicate detection (same email across
 * postings), status emails per catalog. Terminal stages lock moves.
 */
#[Layout('layouts.admin')]
class ApplicationsPipeline extends Component
{
    #[Url]
    public string $jobFilter = '';

    public ?string $noteTarget = null;

    public string $noteText = '';

    public function moveApplication(string $applicationId, string $status): void
    {
        $application = JobApplication::query()->findOrFail($applicationId);
        $this->authorize('update', $application);

        $from = $application->status;
        $to = ApplicationStatus::from($status);

        if ($from === $to) {
            return;
        }

        if ($application->isTerminal()) {
            throw ValidationException::withMessages(['pipeline' => "{$from->label()} is terminal — reopen via a note to HR instead."]);
        }

        $application->forceFill(['status' => $to])->save();
        $application->forceFill(['notes' => array_merge($application->notes ?? [], [[
            'type' => 'status', 'at' => now()->toIso8601String(),
            'note' => $from->label().' → '.$to->label(),
        ]])])->save();

        ApplicationStatusChanged::dispatch($application, $from, $to);
        ActivityLogger::log('admin', 'update', $application, ['status' => [$from->value => $to->value]]);

        $this->dispatch('notify', tone: 'success', message: $application->applicant_name.' → '.$to->label());
    }

    public function rate(string $applicationId, int $rating): void
    {
        $application = JobApplication::query()->findOrFail($applicationId);
        $this->authorize('update', $application);

        $application->forceFill(['rating' => max(1, min(5, $rating))])->save();
    }

    public function openNote(string $applicationId): void
    {
        $this->noteTarget = $applicationId;
        $this->noteText = '';
    }

    public function addNote(): void
    {
        $this->validate(['noteText' => ['required', 'string', 'min:2', 'max:1000']]);

        $application = JobApplication::query()->findOrFail((string) $this->noteTarget);
        $this->authorize('update', $application);

        $application->forceFill(['notes' => array_merge($application->notes ?? [], [[
            'type' => 'note', 'at' => now()->toIso8601String(), 'note' => $this->noteText,
        ]])])->save();

        $this->reset('noteTarget', 'noteText');
        $this->dispatch('notify', tone: 'success', message: 'Note saved.');
    }

    public function render(): View
    {
        $this->authorize('viewAny', JobApplication::class);

        $base = JobApplication::query()
            ->with('posting:id,slug,title')
            ->when($this->jobFilter !== '', fn ($q) => $q->where('job_posting_id', $this->jobFilter));

        $columns = collect(ApplicationStatus::pipeline())
            ->map(fn (ApplicationStatus $status): array => [
                'status' => $status,
                'applications' => (clone $base)->where('status', $status)->orderByDesc('rating')->orderByDesc('created_at')->limit(30)->get(),
            ]);

        $duplicateEmails = (clone $base)
            ->selectRaw('applicant_email, count(*) as n')
            ->groupBy('applicant_email')
            ->havingRaw('count(*) > 1')
            ->pluck('n', 'applicant_email');

        return view('careers.livewire.applications-pipeline', [
            'columns' => $columns,
            'jobs' => JobPosting::query()->orderBy('title')->get(['id', 'title']),
            'duplicateEmails' => $duplicateEmails,
            'canSeePii' => auth()->user()->can('viewPii', JobApplication::class),
        ]);
    }

    /** Signed resume download (15 min) — permission-checked here too. */
    public function downloadResume(string $application)
    {
        $application = JobApplication::query()->findOrFail($application);
        $this->authorize('viewPii', JobApplication::class);

        abort_unless($application->resume_path && Storage::disk(JobApplication::RESUME_DISK)->exists($application->resume_path), 404);

        ActivityLogger::log('admin', 'export', $application, ['action' => 'resume_download']);

        return Storage::disk(JobApplication::RESUME_DISK)->download($application->resume_path);
    }
}
