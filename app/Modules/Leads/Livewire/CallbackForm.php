<?php

namespace App\Modules\Leads\Livewire;

use App\Modules\Leads\Enums\LeadSource;
use App\Modules\Leads\Enums\LeadType;
use App\Modules\Leads\Livewire\Concerns\InteractsWithLeadForms;
use App\Modules\Leads\Models\Lead;
use App\Modules\Leads\Services\LeadIntakeService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Callback island (03-leads-crm §3 — the 2-hour SLA promise): phone +
 * name + preferred window. Sitewide footer link / popup (E-block) and
 * mobile sticky bar (E8) ride this.
 */
class CallbackForm extends Component
{
    use InteractsWithLeadForms;

    /** @var array<string, mixed> */
    public array $form = [
        'phone' => '',
        'name' => '',
        'window' => 'asap',
        'consent' => false,
    ];

    protected function bucket(): string
    {
        return 'callback';
    }

    public function mount(): void
    {
        $this->mountLeadForm();
    }

    protected function draftFields(): array
    {
        return $this->form;
    }

    protected function fillDraft(array $draft): void
    {
        foreach (array_keys($this->form) as $key) {
            if (array_key_exists($key, $draft)) {
                $this->form[$key] = $draft[$key];
            }
        }
    }

    protected function formRules(): array
    {
        return [
            'form.phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'form.name' => ['nullable', 'string', 'max:120'],
            'form.window' => ['required', 'in:asap,morning,afternoon,evening'],
            'form.consent' => ['accepted'],
        ];
    }

    protected function intakePayload(): array
    {
        return [
            'source' => LeadSource::Contact,
            'type' => LeadType::Callback,
            'name' => $this->form['name'] ?: 'Callback request',
            'email' => 'callback+'.substr((string) md5($this->form['phone']), 0, 12).'@sewahospitality.com',
            'phone' => $this->form['phone'],
            'message' => 'Preferred window: '.match ($this->form['window']) {
                'morning' => 'Morning (09:00–12:00 IST)',
                'afternoon' => 'Afternoon (12:00–17:00 IST)',
                'evening' => 'Evening (17:00–19:00 IST)',
                default => 'As soon as possible',
            },
            'idempotency_key' => $this->idempotencyKey,
            'consent_version' => (string) config('sewa.privacy_version'),
            'enrichment' => ['preferred_window' => $this->form['window']],
            'utm' => $this->utm(),
        ];
    }

    protected function execute(): mixed
    {
        return app(LeadIntakeService::class)->create($this->intakePayload(), request());
    }

    protected function handleSuccess(mixed $result, bool $replayed): void
    {
        /** @var Lead $result */
        $this->redirect(route('thank-you', ['source' => 'callback', 'ref' => $result->getKey()]), navigate: true);
    }

    public function render(): View
    {
        return view('leads.livewire.callback-form');
    }
}
