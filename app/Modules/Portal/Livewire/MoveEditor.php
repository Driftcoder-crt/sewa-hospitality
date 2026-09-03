<?php

namespace App\Modules\Portal\Livewire;

use App\Models\User;
use App\Modules\Portal\Enums\ChecklistStatus;
use App\Modules\Portal\Enums\DocumentCategory;
use App\Modules\Portal\Enums\DocumentVisibility;
use App\Modules\Portal\Enums\MoveStage;
use App\Modules\Portal\Enums\MoveStatus;
use App\Modules\Portal\Events\ChecklistItemDone;
use App\Modules\Portal\Events\DocumentPublished;
use App\Modules\Portal\Models\PortalChecklistItem;
use App\Modules\Portal\Models\PortalDocument;
use App\Modules\Portal\Models\PortalMove;
use App\Modules\Portal\Services\MoveStageMachine;
use App\Modules\Services\Models\Service;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Move editor (04-client-portal §4.1–4.3): details, stage machine
 * (guarded transitions only), checklist builder (done from either
 * side), document upload + publish (visibility matrix + expiry), and
 * the service-combo template presets (§4.6).
 */
#[Layout('layouts.admin')]
class MoveEditor extends Component
{
    use WithFileUploads;

    public PortalMove $move;

    /** Editor fields */
    public string $assigneeName = '';

    public string $assigneeEmail = '';

    public string $originCity = '';

    public string $moveDate = '';

    public string $summary = '';

    public string $status = 'active';

    public string $employeeUserId = '';

    public string $consultantUserId = '';

    /** @var list<string> */
    public array $serviceIds = [];

    /** Checklist builder */
    public string $newItemTitle = '';

    public string $newItemDetail = '';

    public string $newItemDue = '';

    /** Document upload */
    public $documentFile;

    public string $documentTitle = '';

    public string $documentCategory = 'other';

    public string $documentVisibility = 'both';

    public string $documentExpiry = '';

    public function mount(PortalMove $move): void
    {
        $this->authorize('view', $move);

        $this->move = $move;
        $this->assigneeName = (string) $move->assignee_name;
        $this->assigneeEmail = (string) $move->assignee_email;
        $this->originCity = (string) $move->origin_city;
        $this->moveDate = $move->move_date?->format('Y-m-d') ?? '';
        $this->summary = (string) $move->summary;
        $this->status = $move->status->value;
        $this->employeeUserId = (string) ($move->employee_user_id ?? '');
        $this->consultantUserId = (string) ($move->primary_consultant_user_id ?? '');
        $this->serviceIds = (array) ($move->service_ids ?? []);
    }

    public function render(): View
    {
        $this->move->refresh()->load(['checklistItems', 'documents.media', 'consultant', 'employee']);

        $machine = app(MoveStageMachine::class);

        return view('portal.livewire.move-editor', [
            'stageOptions' => MoveStage::options(),
            'allowedTargets' => $machine->allowedTargets($this->move),
            'statusOptions' => MoveStatus::options(),
            'services' => Service::query()->where('status', 'published')->orderBy('name')->get(['id', 'name']),
            'staffUsers' => $this->staffOptions(),
            'clientUsers' => $this->move->organization?->users()->orderBy('name')->get(['id', 'name', 'email']) ?? collect(),
            'categories' => DocumentCategory::options(),
            'visibilities' => DocumentVisibility::cases(),
        ]);
    }

    public function saveDetails(): void
    {
        $this->authorize('update', $this->move);

        $this->validate([
            'assigneeName' => ['required', 'string', 'max:190'],
            'assigneeEmail' => ['nullable', 'email', 'max:190'],
            'originCity' => ['nullable', 'string', 'max:120'],
            'moveDate' => ['nullable', 'date'],
            'status' => ['required', 'in:active,on_hold,cancelled'],
        ]);

        $this->move->forceFill([
            'assignee_name' => $this->assigneeName,
            'assignee_email' => $this->assigneeEmail ?: null,
            'origin_city' => $this->originCity ?: null,
            'move_date' => $this->moveDate ?: null,
            'summary' => $this->summary ?: null,
            'status' => $this->status,
            'employee_user_id' => $this->employeeUserId ?: null,
            'primary_consultant_user_id' => $this->consultantUserId ?: null,
            'service_ids' => array_values($this->serviceIds),
        ])->save();

        ActivityLogger::log('admin', 'update', $this->move, ['section' => 'details']);

        $this->dispatch('notify', tone: 'success', message: 'Move details saved.');
    }

    /** Stage machine transition (guarded — illegal targets never render).
     *  Named advanceStage(): Livewire\Component itself declares transition(),
     *  and any child override must match that signature or fatal on load. */
    public function advanceStage(string $stage): void
    {
        $this->authorize('update', $this->move);

        try {
            app(MoveStageMachine::class)->transition($this->move, MoveStage::from($stage), auth()->user());
        } catch (\InvalidArgumentException $e) {
            $this->dispatch('notify', tone: 'error', message: $e->getMessage());

            return;
        }

        $this->dispatch('notify', tone: 'success', message: 'Stage advanced to '.MoveStage::from($stage)->label().'. Employee and managers have been notified.');
    }

    public function addChecklistItem(): void
    {
        $this->authorize('update', $this->move);

        $this->validate([
            'newItemTitle' => ['required', 'string', 'max:190'],
            'newItemDue' => ['nullable', 'date'],
        ]);

        $sort = (int) $this->move->checklistItems()->max('sort') + 1;

        $item = PortalChecklistItem::query()->create([
            'move_record_id' => $this->move->getKey(),
            'title' => $this->newItemTitle,
            'detail' => $this->newItemDetail ?: null,
            'due_at' => $this->newItemDue ?: null,
            'sort' => $sort,
            'status' => ChecklistStatus::Pending,
        ]);

        $this->reset(['newItemTitle', 'newItemDetail', 'newItemDue']);
        ActivityLogger::log('admin', 'create', $item, ['move' => $this->move->reference]);
        $this->dispatch('notify', tone: 'success', message: 'Checklist item added.');
    }

    /** Done from the admin side — ChecklistItemDone fires (04 doc §4.2). */
    public function markItemDone(string $itemId): void
    {
        $this->authorize('update', $this->move);

        $item = PortalChecklistItem::query()->where('move_record_id', $this->move->getKey())->findOrFail($itemId);

        if ($item->status === ChecklistStatus::Done) {
            return;
        }

        $item->forceFill([
            'status' => ChecklistStatus::Done,
            'done_at' => now(),
            'done_by' => auth()->id(),
        ])->save();

        ChecklistItemDone::dispatch($item);
        ActivityLogger::log('admin', 'update', $item, ['action' => 'done']);
        $this->dispatch('notify', tone: 'success', message: 'Task marked done.');
    }

    public function removeItem(string $itemId): void
    {
        $this->authorize('update', $this->move);

        $item = PortalChecklistItem::query()->where('move_record_id', $this->move->getKey())->findOrFail($itemId);

        if ($item->status === ChecklistStatus::Done) {
            $this->dispatch('notify', tone: 'error', message: 'Completed tasks are history — they never get deleted.');

            return;
        }

        $item->delete();
        $this->dispatch('notify', tone: 'success', message: 'Task removed.');
    }

    /**
     * Upload (private) + publish in one step (04 doc §4.3): the publish
     * action notifies the employee — documents never sit half-uploaded.
     */
    public function publishDocument(): void
    {
        $this->authorize('create', PortalDocument::class);

        $this->validate([
            'documentFile' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
            'documentTitle' => ['required', 'string', 'max:190'],
            'documentCategory' => ['required', 'in:visa,lease,inventory,invoice,other'],
            'documentVisibility' => ['required', 'in:employee,manager,both'],
            'documentExpiry' => ['nullable', 'date', 'after:today'],
        ]);

        $file = $this->documentFile;

        $media = $this->move->addMedia($file->getRealPath())
            ->usingFileName(Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.Str::random(6).'.'.$file->getClientOriginalExtension())
            ->toMediaCollection('portal', 'local');

        $media->forceFill([
            'alt_text' => $this->documentTitle,
            'namespace' => 'portal',
        ])->save();

        $document = PortalDocument::query()->create([
            'move_record_id' => $this->move->getKey(),
            'organization_id' => $this->move->organization_id,
            'user_id' => $this->move->employee_user_id,
            'uploaded_by' => auth()->id(),
            'title' => $this->documentTitle,
            'media_id' => $media->getKey(),
            'category' => $this->documentCategory,
            'visible_to' => $this->documentVisibility,
            'expires_at' => $this->documentExpiry ?: null,
        ]);

        $this->reset(['documentFile', 'documentTitle', 'documentCategory', 'documentVisibility', 'documentExpiry']);
        $this->documentCategory = 'other';
        $this->documentVisibility = 'both';

        DocumentPublished::dispatch($document);
        ActivityLogger::log('admin', 'publish', $document, ['title' => $document->title]);

        $this->dispatch('notify', tone: 'success', message: 'Document published — the employee has been notified.');
    }

    public function removeDocument(string $documentId): void
    {
        $this->authorize('update', PortalDocument::query()->findOrFail($documentId));

        $document = PortalDocument::query()->where('move_record_id', $this->move->getKey())->findOrFail($documentId);
        $document->media()->delete();
        $document->delete();

        ActivityLogger::log('admin', 'delete', $document, ['title' => $document->title]);
        $this->dispatch('notify', tone: 'success', message: 'Document removed.');
    }

    /** Template presets (04 doc §4.6) — service combos, one click. */
    public function applyTemplate(string $template): void
    {
        $this->authorize('update', $this->move);

        $presets = [
            'standard-expat' => ['Home Search', 'School Search', 'FRRO & Immigration', 'Settling-in'],
            'corporate-group' => ['Home Search', 'FRRO & Immigration', 'Fleet & Transport', 'Tenancy Management'],
            'quick-landing' => ['Airport Meet & Greet', 'Fleet & Transport', 'Home Search'],
        ];

        $names = $presets[$template] ?? [];

        if ($names === []) {
            $this->dispatch('notify', tone: 'error', message: 'Unknown template.');

            return;
        }

        $ids = Service::query()->whereIn('name', $names)->pluck('id')->all();
        $this->serviceIds = array_values(array_unique([...$this->serviceIds, ...$ids]));

        // Presets also seed the matching checklist skeleton.
        foreach ($names as $index => $name) {
            PortalChecklistItem::query()->create([
                'move_record_id' => $this->move->getKey(),
                'title' => 'Kick off: '.$name,
                'sort' => $index,
                'status' => ChecklistStatus::Pending,
            ]);
        }

        $this->move->forceFill(['service_ids' => $this->serviceIds])->save();
        ActivityLogger::log('admin', 'update', $this->move, ['template' => $template]);

        $this->dispatch('notify', tone: 'success', message: 'Template applied — services and checklist prefilled.');
    }

    private function staffOptions(): Collection
    {
        return User::query()
            ->role('consultant')
            ->orWhereHas('roles', fn ($q) => $q->whereIn('name', ['consultant', 'admin']))
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
