<?php

namespace App\Modules\Careers\Livewire;

use App\Models\User;
use App\Modules\Careers\Enums\Department;
use App\Modules\Careers\Enums\EmployeeStatus;
use App\Modules\Careers\Enums\EmploymentType;
use App\Modules\Careers\Models\AuthorProfile;
use App\Modules\Careers\Models\Employee;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Employees directory (06-hr §4.3): internal registry + the is_public
 * marketing switch feeding leadership grids (D6) and /team pages.
 * Author profiles (§4.4) ride the same screen — bio/credentials for
 * `author` users powering M4 bylines + Person schema.
 */
#[Layout('layouts.admin')]
class EmployeesManager extends Component
{
    use WithPagination;

    public string $tab = 'employees';

    public ?string $editingId = null;

    public bool $showForm = false;

    /** Employee form fields. */
    public string $employeeCode = '';

    public string $fullName = '';

    public string $designation = '';

    public string $department = 'relocation';

    public ?string $joinedAt = null;

    public string $employmentType = 'full';

    public bool $isPublic = false;

    public string $bio = '';

    public string $languages = '';

    public string $certifications = '';

    public string $photoMediaId = '';

    /** Author profile fields (editing user ULID). */
    public string $authorUserId = '';

    public string $authorTitle = '';

    public string $authorBio = '';

    public string $authorLinkedin = '';

    public bool $authorIsPublic = true;

    public function createEmployee(): void
    {
        $this->authorize('create', Employee::class);

        $this->validate([
            'employeeCode' => ['required', 'string', 'max:20', 'unique:employees,employee_code'],
            'fullName' => ['required', 'string', 'max:160'],
            'designation' => ['required', 'string', 'max:120'],
            'department' => ['required', 'in:'.implode(',', array_column(Department::cases(), 'value'))],
            'joinedAt' => ['nullable', 'date', 'before:tomorrow'],
            'employmentType' => ['required', 'in:full,part,contract,intern'],
        ]);

        $employee = Employee::query()->create([
            'employee_code' => $this->employeeCode,
            'full_name' => $this->fullName,
            'designation' => $this->designation,
            'department' => $this->department,
            'joined_at' => $this->joinedAt,
            'employment_type' => $this->employmentType,
            'is_public' => $this->isPublic,
            'bio' => $this->bio ?: null,
            'credentials' => [
                'languages' => array_values(array_filter(array_map('trim', explode(',', $this->languages)))),
                'certifications' => array_values(array_filter(array_map('trim', explode("\n", $this->certifications)))),
            ],
            'photo_media_id' => $this->photoMediaId ?: null,
            'status' => EmployeeStatus::Active,
        ]);

        ActivityLogger::log('admin', 'create', $employee, ['code' => $employee->employee_code]);
        $this->resetForm();
        $this->dispatch('notify', tone: 'success', message: 'Employee added.');
    }

    public function editEmployee(string $id): void
    {
        $employee = Employee::query()->findOrFail($id);
        $this->authorize('update', $employee);

        $this->editingId = $id;
        $this->showForm = true;
        $this->employeeCode = $employee->employee_code;
        $this->fullName = $employee->full_name;
        $this->designation = $employee->designation;
        $this->department = $employee->department->value;
        $this->joinedAt = $employee->joined_at?->format('Y-m-d');
        $this->employmentType = $employee->employment_type;
        $this->isPublic = $employee->is_public;
        $this->bio = (string) $employee->bio;
        $this->languages = implode(', ', $employee->credentials['languages'] ?? []);
        $this->certifications = implode("\n", $employee->credentials['certifications'] ?? []);
        $this->photoMediaId = (string) ($employee->photo_media_id ?? '');
    }

    public function saveEmployee(): void
    {
        $employee = Employee::query()->findOrFail((string) $this->editingId);
        $this->authorize('update', $employee);

        $this->validate([
            'employeeCode' => ['required', 'string', 'max:20', 'unique:employees,employee_code,'.$employee->getKey()],
            'fullName' => ['required', 'string', 'max:160'],
            'designation' => ['required', 'string', 'max:120'],
            'department' => ['required', 'in:'.implode(',', array_column(Department::cases(), 'value'))],
            'joinedAt' => ['nullable', 'date', 'before:tomorrow'],
        ]);

        $employee->forceFill([
            'employee_code' => $this->employeeCode,
            'full_name' => $this->fullName,
            'designation' => $this->designation,
            'department' => $this->department,
            'joined_at' => $this->joinedAt,
            'employment_type' => $this->employmentType,
            'is_public' => $this->isPublic,
            'bio' => $this->bio ?: null,
            'credentials' => [
                'languages' => array_values(array_filter(array_map('trim', explode(',', $this->languages)))),
                'certifications' => array_values(array_filter(array_map('trim', explode("\n", $this->certifications)))),
            ],
            'photo_media_id' => $this->photoMediaId ?: null,
        ])->save();

        ActivityLogger::log('admin', 'update', $employee, ['code' => $employee->employee_code]);
        $this->resetForm();
        $this->dispatch('notify', tone: 'success', message: 'Employee saved.');
    }

    /** Author profile save — bio feeds M4 bylines + Person schema. */
    public function saveAuthor(): void
    {
        $this->validate([
            'authorUserId' => ['required', 'exists:users,id'],
            'authorTitle' => ['nullable', 'string', 'max:120'],
            'authorLinkedin' => ['nullable', 'url:http,https', 'max:255'],
            'authorBio' => ['nullable', 'string', 'max:4000'],
        ]);

        $profile = AuthorProfile::query()->firstOrNew(['user_id' => $this->authorUserId]);
        $this->authorize('updateAuthorProfile', $profile);

        $profile->fill([
            'title' => $this->authorTitle ?: null,
            'bio' => $this->authorBio ?: null,
            'linkedin' => $this->authorLinkedin ?: null,
            'is_public' => $this->authorIsPublic,
        ])->save();

        ActivityLogger::log('admin', 'update', $profile, ['author' => $this->authorUserId]);
        $this->dispatch('notify', tone: 'success', message: 'Author profile saved.');
    }

    public function editAuthor(string $userId): void
    {
        $user = User::query()->findOrFail($userId);
        $profile = AuthorProfile::query()->firstOrNew(['user_id' => $userId]);

        $this->tab = 'authors';
        $this->authorUserId = $userId;
        $this->authorTitle = (string) $profile->title;
        $this->authorBio = (string) $profile->bio;
        $this->authorLinkedin = (string) $profile->linkedin;
        $this->authorIsPublic = $profile->exists ? $profile->is_public : true;
        $this->showForm = true;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'showForm', 'employeeCode', 'fullName', 'designation', 'joinedAt', 'bio', 'languages', 'certifications', 'photoMediaId']);
        $this->department = 'relocation';
        $this->employmentType = 'full';
        $this->isPublic = false;
    }

    public function render(): View
    {
        $this->authorize('viewAny', Employee::class);

        $employees = Employee::query()->orderBy('full_name')->paginate(15);

        $authors = User::query()
            ->role('author')
            ->with('authorProfile')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('careers.livewire.employees-manager', [
            'employees' => $employees,
            'authors' => $authors,
            'departments' => Department::options(),
            'employmentTypes' => EmploymentType::options(),
            'statuses' => EmployeeStatus::options(),
        ]);
    }
}
