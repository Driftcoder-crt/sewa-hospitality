<?php

namespace App\Modules\Leads\Livewire;

use App\Modules\Leads\Livewire\Concerns\InteractsWithLeadForms;
use App\Modules\Leads\Services\NewsletterService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Newsletter capture island (E4 + footer + blog sidebar): email →
 * double opt-in. Inline success (no redirect — the reference's silent
 * "#" action is exactly what we do NOT do). Compact variant serves the
 * A2 split-hero mini-form.
 *
 * Embedded island inside public pages — NO layout attribute.
 */
class NewsletterSignup extends Component
{
    use InteractsWithLeadForms;

    #[Validate('required', message: 'Please enter your email address.')]
    #[Validate('email:filter', message: 'That does not look like a valid email address.')]
    #[Validate('max:190')]
    public string $email = '';

    /** Compact (split-hero mini) vs full rendering. */
    public bool $compact = false;

    /** Subscribed flow state for the inline confirmation. */
    public bool $subscribed = false;

    public function mount(): void
    {
        $this->mountLeadForm();
    }

    protected function bucket(): string
    {
        return 'newsletter';
    }

    protected function draftFields(): array
    {
        return ['email' => $this->email];
    }

    protected function fillDraft(array $draft): void
    {
        if (isset($draft['email'])) {
            $this->email = (string) $draft['email'];
        }
    }

    protected function formRules(): array
    {
        return [
            'email' => ['required', 'email:filter', 'max:190'],
        ];
    }

    protected function intakePayload(): array
    {
        return [];
    }

    protected function execute(): mixed
    {
        return app(NewsletterService::class)->subscribe(
            email: $this->email,
            locale: app()->getLocale(),
            source: 'site:'.($this->compact ? 'mini' : 'block'),
        );
    }

    protected function handleSuccess(mixed $result, bool $replayed): void
    {
        $this->subscribed = true;
        $this->email = '';
    }

    /** Newsletter uses inline confirmation, not the submitLead redirect. */
    public function submitNewsletter(): void
    {
        $this->submitLead();
    }

    public function render(): View
    {
        return view('leads.livewire.newsletter-signup');
    }
}
