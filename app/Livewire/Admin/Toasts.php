<?php

namespace App\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Toast island: collects `notify` events from any admin Livewire
 * component and renders ARIA-announced, dismissible toasts (never
 * self-destructing errors — dismissible on demand, ui-components doc).
 */
class Toasts extends Component
{
    /** @var Collection<int, array{tone: string, message: string, id: string}> */
    public Collection $toasts;

    public function mount(): void
    {
        $this->toasts = collect();
    }

    #[On('notify')]
    public function notify(string $message, string $tone = 'success'): void
    {
        $this->toasts->push([
            'tone' => $tone,
            'message' => $message,
            'id' => (string) Str::uuid(),
        ]);
    }

    public function dismiss(string $id): void
    {
        $this->toasts = $this->toasts->reject(fn (array $toast): bool => $toast['id'] === $id)->values();
    }

    public function render(): View
    {
        return view('admin.toasts');
    }
}
