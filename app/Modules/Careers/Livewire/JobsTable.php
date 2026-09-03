<?php

namespace App\Modules\Careers\Livewire;

use App\Modules\Careers\Enums\Department;
use App\Modules\Careers\Enums\JobStatus;
use App\Modules\Careers\Models\JobPosting;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Job postings admin (06-hr §4.1): CRUD + status machine
 * draft→open→paused→closed + closing dates. Opening a posting stamps
 * published_at and emits JobOpened (analytics hooks later).
 */
#[Layout('layouts.admin')]
class JobsTable extends Component
{
    use WithPagination;

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $q = '';

    public string $title = '';

    public string $department = 'relocation';

    public string $locationText = '';

    public string $employmentType = 'full';

    public bool $showForm = false;

    public function create(): void
    {
        $this->authorize('create', JobPosting::class);

        $this->validate([
            'title' => ['required', 'string', 'max:190'],
            'department' => ['required', 'in:'.implode(',', array_column(Department::cases(), 'value'))],
            'locationText' => ['required', 'string', 'max:160'],
            'employmentType' => ['required', 'in:full,part,contract,intern'],
        ]);

        $posting = JobPosting::query()->create([
            'slug' => $this->uniqueSlug($this->title),
            'title' => $this->title,
            'department' => $this->department,
            'location_text' => $this->locationText,
            'employment_type' => $this->employmentType,
            'status' => JobStatus::Draft,
            'posted_by_user_id' => auth()->id(),
            'locale' => app()->getLocale(),
        ]);

        ActivityLogger::log('admin', 'create', $posting, ['title' => $posting->title]);
        $this->reset('title', 'locationText', 'showForm');
        $this->dispatch('notify', tone: 'success', message: 'Draft created — open it to add the description.');

        $this->redirect(route('admin.jobs.edit', ['job' => $posting->getKey()]), navigate: true);
    }

    public function open(string $id): void
    {
        $posting = JobPosting::query()->findOrFail($id);
        $this->authorize('update', $posting);

        if ($posting->status !== JobStatus::Draft && $posting->status !== JobStatus::Paused) {
            return;
        }

        $posting->forceFill([
            'status' => JobStatus::Open,
            'published_at' => $posting->published_at ?? now(),
        ])->save();

        Event::dispatch('careers.job.opened', [$posting]);
        ActivityLogger::log('admin', 'publish', $posting, ['status' => 'open']);
        $this->dispatch('notify', tone: 'success', message: 'Role is live at '.$posting->publicPath());
    }

    public function pause(string $id): void
    {
        $posting = JobPosting::query()->findOrFail($id);
        $this->authorize('update', $posting);

        $posting->forceFill(['status' => JobStatus::Paused])->save();
        ActivityLogger::log('admin', 'update', $posting, ['status' => 'paused']);
    }

    public function close(string $id): void
    {
        $posting = JobPosting::query()->findOrFail($id);
        $this->authorize('update', $posting);

        $posting->forceFill(['status' => JobStatus::Closed])->save();
        ActivityLogger::log('admin', 'update', $posting, ['status' => 'closed']);
        $this->dispatch('notify', tone: 'success', message: 'Role closed — the page stays up with "see similar".');
    }

    public function render(): View
    {
        $this->authorize('viewAny', JobPosting::class);

        $postings = JobPosting::query()
            ->withCount('applications')
            ->when($this->statusFilter !== '', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->q !== '', fn ($q) => $q->where('title', 'like', '%'.$this->q.'%'))
            ->orderByRaw("case status when 'open' then 0 when 'paused' then 1 when 'draft' then 2 else 3 end")
            ->orderByDesc('published_at')
            ->paginate(15);

        return view('careers.livewire.jobs-table', [
            'postings' => $postings,
            'statuses' => JobStatus::options(),
            'departments' => Department::options(),
        ]);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 2;

        while (JobPosting::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.($i++);
        }

        return $slug;
    }
}
