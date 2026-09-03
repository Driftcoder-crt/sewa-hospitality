<?php

namespace App\Modules\Services\Livewire;

use Livewire\Component;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Models\ServiceCategory;

class ServiceCard extends Component
{
    public Service $service;

    public function render()
    {
        return view('livewire.services.service-card');
    }

    public function viewDetails(): void
    {
        redirect()->route('services.show', $this->service->slug);
    }
}
