<?php

namespace App\Modules\Leads\Livewire;

use App\Modules\Leads\Enums\LeadSource;
use App\Modules\Leads\Enums\LeadType;
use App\Modules\Leads\Livewire\Concerns\InteractsWithLeadForms;
use App\Modules\Leads\Models\Lead;
use App\Modules\Leads\Services\LeadIntakeService;
use App\Modules\Services\Models\Service;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * /contact form island (03-leads-crm §3): name/email/phone/company/
 * message/service/consent. Optional pre-selected service (context prop
 * from the E2 block or query param) rides the submission as lead_tag.
 *
 * Embedded island inside public pages (components/blocks/lead-form) —
 * NO layout attribute: a full-page render must not take the admin shell.
 */
class ContactForm extends Component
{
    use InteractsWithLeadForms;

    /** @var array<string, mixed> */
    public array $form = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'company' => '',
        'service_id' => '',
        'message' => '',
        'consent' => false,
    ];

    /** Pre-selected service id (E2 block context / service pages). */
    public ?string $contextServiceId = null;

    public function mount(): void
    {
        if ($this->contextServiceId) {
            $this->form['service_id'] = $this->contextServiceId;
        }

        $this->mountLeadForm();
    }

    protected function bucket(): string
    {
        return 'contact';
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
            'form.company' => ['nullable', 'string', 'max:160'],
            'form.service_id' => ['nullable', 'exists:services,id'],
            'form.message' => ['required', 'string', 'min:10', 'max:2000'],
            'form.consent' => ['accepted'],
        ];
    }

    protected function intakePayload(): array
    {
        return [
            'source' => LeadSource::Contact,
            'type' => LeadType::Enquiry,
            'name' => $this->form['name'],
            'email' => $this->form['email'],
            'phone' => $this->form['phone'],
            'company' => $this->form['company'] ?: null,
            'message' => $this->form['message'],
            'service_id' => $this->form['service_id'] ?: null,
            'idempotency_key' => $this->idempotencyKey,
            'consent_version' => (string) config('sewa.privacy_version'),
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
        $this->redirect(route('thank-you', ['source' => 'contact', 'ref' => $result->getKey()]), navigate: true);
    }

    public function render(): View
    {
        return view('leads.livewire.contact-form', [
            'services' => Service::query()
                ->published()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}
