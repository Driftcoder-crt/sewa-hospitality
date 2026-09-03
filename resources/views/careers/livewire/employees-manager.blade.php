{{-- Employees + author profiles (06-hr §4.3–4.4). --}}
<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="font-display text-2xl">People</h1>
        <div class="flex rounded-lg border border-line p-1" role="tablist">
            <button type="button" wire:click="$set('tab', 'employees')" role="tab"
                    :aria-selected="$wire.tab === 'employees'"
                    @class(['min-h-[40px] rounded-md px-4 text-sm font-medium', 'bg-paper-3 text-ink' => $tab === 'employees', 'text-ink-soft' => $tab !== 'employees'])>
                Employees
            </button>
            <button type="button" wire:click="$set('tab', 'authors')" role="tab"
                    :aria-selected="$wire.tab === 'authors'"
                    @class(['min-h-[40px] rounded-md px-4 text-sm font-medium', 'bg-paper-3 text-ink' => $tab === 'authors', 'text-ink-soft' => $tab !== 'authors'])>
                Authors
            </button>
        </div>
    </div>

    @if ($tab === 'employees')
        @if ($showForm)
            <form wire:submit="{{ $editingId ? 'saveEmployee' : 'createEmployee' }}" class="mt-4 grid gap-3 rounded-xl border border-line bg-paper-2 p-5 sm:grid-cols-2">
                <div>
                    <label for="emp-code" class="text-sm font-medium">Employee code</label>
                    <input id="emp-code" type="text" wire:model.live.debounce.300ms="employeeCode" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                    @error('employeeCode') <p class="text-xs text-danger-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="emp-name" class="text-sm font-medium">Full name</label>
                    <input id="emp-name" type="text" wire:model.live.debounce.300ms="fullName" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                    @error('fullName') <p class="text-xs text-danger-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="emp-desig" class="text-sm font-medium">Designation</label>
                    <input id="emp-desig" type="text" wire:model.live.debounce.300ms="designation" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                    @error('designation') <p class="text-xs text-danger-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="emp-dept" class="text-sm font-medium">Department</label>
                    <select id="emp-dept" wire:model.live="department" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                        @foreach ($departments as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="emp-joined" class="text-sm font-medium">Joined</label>
                    <input id="emp-joined" type="date" wire:model.live="joinedAt" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                </div>
                <div>
                    <label for="emp-type" class="text-sm font-medium">Employment type</label>
                    <select id="emp-type" wire:model.live="employmentType" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                        @foreach ($employmentTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="emp-bio" class="text-sm font-medium">Public bio (shown when public)</label>
                    <textarea id="emp-bio" wire:model.live.debounce.300ms="bio" rows="3" class="mt-1 w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-sm"></textarea>
                </div>
                <div>
                    <label for="emp-langs" class="text-sm font-medium">Languages (comma-separated)</label>
                    <input id="emp-langs" type="text" wire:model.live.debounce.300ms="languages" placeholder="English, Hindi, Japanese" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                </div>
                <div>
                    <label for="emp-photo" class="text-sm font-medium">Photo media id</label>
                    <input id="emp-photo" type="text" wire:model.live.debounce.300ms="photoMediaId" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label for="emp-certs" class="text-sm font-medium">Certifications (one per line)</label>
                    <textarea id="emp-certs" wire:model.live.debounce.300ms="certifications" rows="2" class="mt-1 w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-sm"></textarea>
                </div>
                <label class="flex items-center gap-2 text-sm sm:col-span-2">
                    <input type="checkbox" wire:model.live="isPublic" class="h-5 w-5">
                    Public profile (leadership grid, /team page, consultant cards)
                </label>
                <div class="flex gap-2 sm:col-span-2">
                    <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-brand px-5 text-sm font-semibold text-brand-ink">
                        {{ $editingId ? 'Save employee' : 'Add employee' }}
                    </button>
                    <button type="button" wire:click="resetForm" class="inline-flex min-h-[44px] items-center rounded-lg border border-line px-4 text-sm">Cancel</button>
                </div>
            </form>
        @else
            <button type="button" wire:click="$set('showForm', true)"
                    class="mt-4 inline-flex min-h-[44px] items-center rounded-lg bg-brand px-4 text-sm font-semibold text-brand-ink">
                Add employee
            </button>
        @endif

        <div class="mt-4 overflow-hidden rounded-xl border border-line bg-paper-2">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-3 py-3">Employee</th>
                        <th class="hidden px-3 py-3 md:table-cell">Department</th>
                        <th class="px-3 py-3">Public</th>
                        <th class="px-3 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-3 py-3">
                                <span class="font-medium">{{ $employee->full_name }}</span>
                                <div class="text-xs text-ink-muted">{{ $employee->designation }} · {{ $employee->employee_code }}</div>
                            </td>
                            <td class="hidden px-3 py-3 text-xs text-ink-muted md:table-cell">{{ $employee->department->label() }}</td>
                            <td class="px-3 py-3">
                                <span @class(['text-xs font-semibold', 'text-brand' => $employee->is_public, 'text-ink-muted' => ! $employee->is_public])>
                                    {{ $employee->is_public ? 'Public' : 'Internal' }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-end">
                                <button type="button" wire:click="editEmployee('{{ $employee->id }}')" class="text-xs font-medium text-brand hover:underline">Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-10 text-center text-sm text-ink-muted">The directory is empty — leadership grid (D6) and /team pages read from here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $employees->links() }}</div>
    @else
        <div class="mt-4 overflow-hidden rounded-xl border border-line bg-paper-2">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-3 py-3">Author</th>
                        <th class="hidden px-3 py-3 md:table-cell">Title</th>
                        <th class="px-3 py-3">Public</th>
                        <th class="px-3 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($authors as $author)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-3 py-3">
                                <span class="font-medium">{{ $author->name }}</span>
                                <div class="text-xs text-ink-muted">{{ $author->email }}</div>
                            </td>
                            <td class="hidden px-3 py-3 text-xs text-ink-muted md:table-cell">{{ $author->authorProfile?->title ?? '—' }}</td>
                            <td class="px-3 py-3 text-xs">{{ $author->authorProfile?->exists && $author->authorProfile->is_public ? 'Public' : 'Hidden' }}</td>
                            <td class="px-3 py-3 text-end">
                                <button type="button" wire:click="editAuthor('{{ $author->id }}')" class="text-xs font-medium text-brand hover:underline">Edit profile</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-10 text-center text-sm text-ink-muted">No users with the author role yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($authorUserId)
            <form wire:submit="saveAuthor" class="mt-4 grid gap-3 rounded-xl border border-line bg-paper-2 p-5">
                <div>
                    <label for="auth-title" class="text-sm font-medium">Title (e.g. "Head of Global Mobility")</label>
                    <input id="auth-title" type="text" wire:model.live.debounce.300ms="authorTitle" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                </div>
                <div>
                    <label for="auth-bio" class="text-sm font-medium">Bio (feeds bylines + Person schema)</label>
                    <textarea id="auth-bio" wire:model.live.debounce.300ms="authorBio" rows="3" class="mt-1 w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-sm"></textarea>
                </div>
                <div>
                    <label for="auth-li" class="text-sm font-medium">LinkedIn URL</label>
                    <input id="auth-li" type="url" wire:model.live.debounce.300ms="authorLinkedin" class="mt-1 min-h-[44px] w-full rounded-lg border border-line bg-paper px-3 text-sm">
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model.live="authorIsPublic" class="h-5 w-5"> Public profile page
                </label>
                <button type="submit" class="inline-flex min-h-[44px] w-full items-center justify-center rounded-lg bg-brand px-5 text-sm font-semibold text-brand-ink sm:w-48">
                    Save author profile
                </button>
            </form>
        @endif
    @endif
</div>
