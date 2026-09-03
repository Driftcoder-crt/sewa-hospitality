<?php

namespace App\Modules\Leads\Livewire;

use App\Modules\Cities\Models\City;
use App\Modules\Leads\Enums\LeadSource;
use App\Modules\Leads\Enums\LeadType;
use App\Modules\Leads\Livewire\Concerns\InteractsWithLeadForms;
use App\Modules\Leads\Models\Lead;
use App\Modules\Leads\Services\LeadIntakeService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Quote request island (03-leads-crm §3 — "richest intent", fast-lane
 * to pipeline stage proposal): service context, city, requirements,
 * honest budget hint. On service/housing pages the context pre-fills
 * service/city and the fields ride every submission.
 *
 * Embedded island inside public pages — NO layout attribute.
 */
class QuoteForm extends Component
{
    use InteractsWithLeadForms;

    /** @var array<string, mixed> */
    public array $form = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'company' => '',
        'city_id' => '',
        'requirements' => '',
        'budget_hint' => '',
        'consent' => false,
    ];

    /** Context: pre-set service (service page) — locked in the UI. */
    public ?string $contextServiceId = null;

    public ?string $contextServiceName = null;

    /** Context: pre-set city (housing pages). */
    public ?string $contextCityId = null;

    protected function bucket(): string
    {
        return 'quote';
    }

    public function mount(): void
    {
        if ($this->contextCityId) {
            $this->form['city_id'] = $this->contextCityId;
        }

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
            'form.name' => ['required', 'string', 'max:120'],
            'form.email' => ['required', 'email:filter', 'max:190'],
            'form.phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'form.company' => ['required', 'string', 'max:160'],
            'form.city_id' => ['nullable', 'exists:cities,id'],
            'form.requirements' => ['required', 'string', 'min:20', 'max:3000'],
            'form.budget_hint' => ['nullable', 'string', 'max:60'],
            'form.consent' => ['accepted'],
        ];
    }

    protected function intakePayload(): array
    {
        return [
            'source' => LeadSource::ServicePage,
            'type' => LeadType::QuoteRequest,
            'name' => $this->form['name'],
            'email' => $this->form['email'],
            'phone' => $this->form['phone'],
            'company' => $this->form['company'],
            'message' => $this->form['requirements'],
            'service_id' => $this->contextServiceId,
            'city_id' => $this->form['city_id'] ?: $this->contextCityId,
            'idempotency_key' => $this->idempotencyKey,
            'consent_version' => (string) config('sewa.privacy_version'),
            'enrichment' => ['budget_hint' => $this->form['budget_hint'] ?: null],
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
        $this->redirect(route('thank-you', ['source' => 'quote', 'ref' => $result->getKey()]), navigate: true);
    }

    public function render(): View
    {
        return view('leads.livewire.quote-form', [
            'cities' => City::query()->where('status', 'published')->orderBy('name')->get(['id', 'name']),
            'budgetBands' => [
                '< ₹50,000',
                '₹50,000 – ₹2,00,000',
                '₹2,00,000 – ₹10,00,000',
                '₹10,00,000+',
                'Not sure yet',
            ],
        ]);
    }
}
