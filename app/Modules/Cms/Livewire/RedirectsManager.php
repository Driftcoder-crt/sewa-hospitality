<?php

namespace App\Modules\Cms\Livewire;

use App\Modules\Cms\Enums\RedirectCode;
use App\Modules\Cms\Models\Redirect;
use App\Modules\Cms\Services\RedirectService;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Redirects admin (04-modules/01-cms.md §4.5): CRUD + CSV import +
 * activate/deactivate. admin+ permission (policy) — redirects shape
 * SEO equity, editors don't touch them. Hit counters show what each
 * rule catches.
 */
#[Layout('layouts.admin')]
class RedirectsManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $from = '';

    public string $to = '';

    public int $code = 301;

    public string $note = '';

    public bool $active = true;

    public bool $showForm = false;

    public ?string $editingId = null;

    /** CSV import: header row `from,to,code` (code optional, default 301). */
    public $csv;

    public function create(): void
    {
        $this->authorize('create', Redirect::class);

        $validated = $this->validateEntry();
        if ($validated === null) {
            return;
        }

        $redirect = Redirect::query()->create($validated);
        ActivityLogger::log('admin', 'create', $redirect, ['from' => $redirect->from, 'to' => $redirect->to]);

        $this->resetForm();
        $this->dispatch('notify', tone: 'success', message: 'Redirect created.');
    }

    public function edit(string $id): void
    {
        $redirect = Redirect::query()->findOrFail($id);
        $this->authorize('update', $redirect);

        $this->editingId = $id;
        $this->showForm = true;
        $this->from = $redirect->from;
        $this->to = $redirect->to;
        $this->code = $redirect->code->value;
        $this->note = (string) $redirect->note;
        $this->active = $redirect->active;
    }

    public function update(): void
    {
        $redirect = Redirect::query()->findOrFail((string) $this->editingId);
        $this->authorize('update', $redirect);

        $validated = $this->validateEntry(ignoreId: $this->editingId);
        if ($validated === null) {
            return;
        }

        $redirect->fill($validated)->save();
        ActivityLogger::log('admin', 'update', $redirect, ['from' => $redirect->from, 'to' => $redirect->to]);

        $this->resetForm();
        $this->dispatch('notify', tone: 'success', message: 'Redirect updated.');
    }

    public function toggle(string $id): void
    {
        $redirect = Redirect::query()->findOrFail($id);
        $this->authorize('update', $redirect);

        $redirect->active = ! $redirect->active;
        $redirect->save();

        ActivityLogger::log('admin', 'update', $redirect, ['active' => $redirect->active]);
    }

    public function delete(string $id): void
    {
        $redirect = Redirect::query()->findOrFail($id);
        $this->authorize('delete', $redirect);

        ActivityLogger::log('admin', 'delete', $redirect, ['from' => $redirect->from]);
        $redirect->delete();

        $this->dispatch('notify', tone: 'success', message: 'Redirect deleted.');
    }

    public function importCsv(): void
    {
        $this->authorize('create', Redirect::class);

        $this->validate(['csv' => 'required|file|mimes:csv,txt|max:2048']);

        $handle = fopen($this->csv->getRealPath(), 'rb');
        if ($handle === false) {
            $this->addError('csv', 'Could not read the file.');

            return;
        }

        $imported = 0;
        $row = 0;
        while (($data = fgetcsv($handle, 1000)) !== false) {
            $row++;
            if ($row === 1 && mb_strtolower(trim((string) $data[0])) === 'from') {
                continue; // header
            }

            $from = trim((string) ($data[0] ?? ''));
            $to = trim((string) ($data[1] ?? ''));
            $code = (int) ($data[2] ?? 301) === 302 ? 302 : 301;

            if ($from === '' || $to === '') {
                continue;
            }

            try {
                Redirect::query()->updateOrCreate(['from' => $from], [
                    'to' => $to,
                    'code' => $code,
                    'note' => 'CSV import',
                    'active' => true,
                ]);
                $imported++;
            } catch (\Throwable) {
                continue; // duplicates of the same normalized path resolve via updateOrCreate
            }
        }
        fclose($handle);

        ActivityLogger::log('admin', 'create', null, ['csv_import' => $imported.' redirects']);
        $this->reset('csv');
        $this->dispatch('notify', tone: 'success', message: $imported.' redirect(s) imported.');
    }

    public function render(): View
    {
        $this->authorize('viewAny', Redirect::class);

        return view('cms.livewire.redirects-manager', [
            'redirects' => Redirect::query()->orderBy('from')->paginate(15),
            'codes' => RedirectCode::options(),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['from', 'to', 'note', 'active', 'showForm', 'editingId']);
        $this->code = 301;
        $this->active = true;
    }

    /** @return array{from: string, to: string, code: int, note: string|null, active: bool}|null */
    private function validateEntry(?string $ignoreId = null): ?array
    {
        $from = RedirectService::normalize($this->from);
        $to = trim($this->to);

        if ($from === '/' || $from === '') {
            $this->addError('from', 'The source path cannot be the site root.');

            return null;
        }

        if ($to === '') {
            $this->addError('to', 'A destination is required.');

            return null;
        }

        if (! str_starts_with($to, '/') && ! str_starts_with($to, 'https://') && ! str_starts_with($to, 'http://')) {
            $this->addError('to', 'Destination must be a path (/about) or an absolute URL.');

            return null;
        }

        $collision = Redirect::query()
            ->where('from', $from)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
        if ($collision) {
            $this->addError('from', 'A redirect for that path already exists.');

            return null;
        }

        return [
            'from' => $from,
            'to' => $to,
            'code' => $this->code === 302 ? 302 : 301,
            'note' => trim($this->note) ?: null,
            'active' => $this->active,
        ];
    }
}
