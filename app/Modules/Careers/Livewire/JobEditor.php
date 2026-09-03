<?php

namespace App\Modules\Careers\Livewire;

use App\Modules\Careers\Enums\Department;
use App\Modules\Careers\Enums\EmploymentType;
use App\Modules\Careers\Enums\JobStatus;
use App\Modules\Careers\Models\JobPosting;
use App\Modules\Cities\Models\City;
use App\Support\Audit\ActivityLogger;
use App\Support\Cms\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Job editor (06-hr §4.1): role content with sanitized HTML sections,
 * status machine, closing date, location, department, and a live
 * rendered-content preview pane. Autosave notes: explicit save (drafts
 * are internal work — no public impact window).
 */
#[Layout('layouts.admin')]
class JobEditor extends Component
{
    public JobPosting $job;

    public string $title = '';

    public string $department = 'relocation';

    public string $locationCityId = '';

    public string $locationText = '';

    public string $employmentType = 'full';

    public string $experienceMin = '';

    public string $experienceMax = '';

    public string $descriptionHtml = '';

    public string $responsibilitiesHtml = '';

    public string $skillsHtml = '';

    public ?string $closesAt = null;

    public string $appliesToEmail = '';

    public function mount(JobPosting $job): void
    {
        $this->authorize('update', $job);
        $this->syncForm();
    }

    public function syncForm(): void
    {
        $this->title = $this->job->title;
        $this->department = $this->job->department->value;
        $this->locationCityId = (string) ($this->job->location_city_id ?? '');
        $this->locationText = $this->job->location_text;
        $this->employmentType = $this->job->employment_type->value;
        $this->experienceMin = (string) ($this->job->experience_min ?? '');
        $this->experienceMax = (string) ($this->job->experience_max ?? '');
        $this->descriptionHtml = (string) $this->job->description_html;
        $this->responsibilitiesHtml = (string) $this->job->responsibilities_html;
        $this->skillsHtml = (string) $this->job->skills_html;
        $this->closesAt = $this->job->closes_at?->format('Y-m-d');
        $this->appliesToEmail = (string) ($this->job->applies_to_email ?? '');
    }

    public function save(): void
    {
        $this->validate([
            'title' => ['required', 'string', 'max:190'],
            'department' => ['required', 'in:'.implode(',', array_column(Department::cases(), 'value'))],
            'locationText' => ['required', 'string', 'max:160'],
            'employmentType' => ['required', 'in:full,part,contract,intern'],
            'locationCityId' => ['nullable', 'exists:cities,id'],
            'experienceMin' => ['nullable', 'integer', 'between:0,40'],
            'experienceMax' => ['nullable', 'integer', 'between:0,40'],
            'closesAt' => ['nullable', 'date', 'after:2024-01-01'],
            'appliesToEmail' => ['nullable', 'email:filter', 'max:190'],
            'descriptionHtml' => ['nullable', 'string', 'max:20000'],
            'responsibilitiesHtml' => ['nullable', 'string', 'max:20000'],
            'skillsHtml' => ['nullable', 'string', 'max:20000'],
        ]);

        $this->job->forceFill([
            'title' => $this->title,
            'department' => $this->department,
            'location_city_id' => $this->locationCityId ?: null,
            'location_text' => $this->locationText,
            'employment_type' => $this->employmentType,
            'experience_min' => $this->experienceMin !== '' ? (int) $this->experienceMin : null,
            'experience_max' => $this->experienceMax !== '' ? (int) $this->experienceMax : null,
            'description_html' => $this->descriptionHtml !== '' ? HtmlSanitizer::clean($this->descriptionHtml) : null,
            'responsibilities_html' => $this->responsibilitiesHtml !== '' ? HtmlSanitizer::clean($this->responsibilitiesHtml) : null,
            'skills_html' => $this->skillsHtml !== '' ? HtmlSanitizer::clean($this->skillsHtml) : null,
            'closes_at' => $this->closesAt,
            'applies_to_email' => $this->appliesToEmail ?: null,
        ])->save();

        ActivityLogger::log('admin', 'update', $this->job, ['title' => $this->title]);
        $this->syncForm();
        $this->dispatch('notify', tone: 'success', message: 'Role saved.');
    }

    public function render(): View
    {
        $this->authorize('view', $this->job);

        return view('careers.livewire.job-editor', [
            'departments' => Department::options(),
            'employmentTypes' => EmploymentType::options(),
            'cities' => City::query()->where('status', 'published')->orderBy('name')->get(['id', 'name']),
            'statuses' => JobStatus::options(),
        ]);
    }
}
