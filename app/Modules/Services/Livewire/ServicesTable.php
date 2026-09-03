<?php

namespace App\Modules\Services\Livewire;

use App\Modules\Services\Enums\ServiceFamily;
use App\Modules\Services\Enums\ServiceStatus;
use App\Modules\Services\Events\ServicePublished;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Services\ServicePublishGate;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Services tree + editor entry (04-modules/02-services-module.md §4):
 * tree view with status chips and per-node actions; ordering within
 * sibling sets drives hub card order. The full block-canvas editor is
 * ServiceEditor; lead_tag changes are admin+ (analytics continuity).
 */
#[Layout('layouts.admin')]
class ServicesTable extends Component
{
    #[Url]
    public string $status = '';

    public function create(): void
    {
        $this->authorize('create', Service::class);

        $service = Service::query()->create([
            'name' => 'New service',
            'slug' => 'new-service-'.mb_strtolower(Str::random(4)),
            'family' => ServiceFamily::EmployeeMobility->value,
            'status' => ServiceStatus::Draft->value,
            'lead_tag' => 'relocation',
            'content_blocks' => [],
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        ActivityLogger::log('admin', 'create', $service, ['name' => $service->name]);

        $this->redirectRoute('admin.services.edit', ['service' => $service->getKey()]);
    }

    public function publish(string $serviceId, ServicePublishGate $gate): void
    {
        $service = Service::query()->findOrFail($serviceId);
        $this->authorize('publish', $service);

        $inspection = $gate->inspect($service);

        if ($inspection['errors'] !== []) {
            $this->dispatch('notify', tone: 'danger', message: 'Publish blocked: '.implode(' ', array_values($inspection['errors'])));

            return;
        }

        DB::transaction(function () use ($service): void {
            $service->status = ServiceStatus::Published;
            $service->updated_by = auth()->id();
            $service->save();
            $service->refresh();

            event(new ServicePublished($service));
        });

        ActivityLogger::log('admin', 'publish', $service, ['slug' => $service->slug]);
        $this->dispatch('notify', tone: 'success', message: '"'.$service->name.'" published.');
    }

    public function unpublish(string $serviceId): void
    {
        $service = Service::query()->findOrFail($serviceId);
        $this->authorize('publish', $service);

        $service->status = ServiceStatus::Archived;
        $service->save();

        ActivityLogger::log('admin', 'unpublish', $service, ['slug' => $service->slug]);
        $this->dispatch('notify', tone: 'success', message: '"'.$service->name.'" archived.');
    }

    public function moveUp(string $serviceId): void
    {
        $service = Service::query()->findOrFail($serviceId);
        $this->authorize('update', $service);

        // Sibling set = same parent (roots share parent_id null).
        $sibling = Service::query()
            ->where('parent_id', $service->parent_id)
            ->where('sort', '<', $service->sort)
            ->orderByDesc('sort')
            ->first();

        if ($sibling === null) {
            return;
        }

        [$service->sort, $sibling->sort] = [$sibling->sort, $service->sort];
        $service->save();
        $sibling->save();
    }

    public function render(): View
    {
        $this->authorize('viewAny', Service::class);

        $services = Service::query()
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->orderBy('family')
            ->orderBy('parent_id')
            ->orderBy('sort')
            ->orderBy('name')
            ->get();

        return view('services.livewire.services-table', [
            'services' => $services,
            'statuses' => ServiceStatus::options(),
            'families' => ServiceFamily::options(),
        ]);
    }
}
