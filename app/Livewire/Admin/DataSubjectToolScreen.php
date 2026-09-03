<?php

namespace App\Livewire\Admin;

use App\Support\Audit\ActivityLogger;
use App\Support\Security\DataSubjectTool;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Data-subject tool (05-security-reliability.md §1.4, DPDP right to
 * access + erasure). Admin+ only. Every export and every erasure is
 * audit-logged with the operator + the subject email; invoices,
 * payments and the audit trail are never touched.
 */
#[Layout('layouts.admin')]
final class DataSubjectToolScreen extends Component
{
    public string $email = '';

    public bool $confirmErase = false;

    public ?array $summary = null;

    public ?string $lastAction = null;

    public function export(): StreamedResponse
    {
        $this->validate(['email' => 'required|email']);

        ActivityLogger::log('admin', 'export', null, [
            'tool' => 'data-subject',
            'subject' => mb_strtolower($this->email),
        ]);

        $payload = DataSubjectTool::export($this->email);
        $this->summary = collect($payload)
            ->filter(fn ($section): bool => is_array($section))
            ->map(fn ($section): int => count($section))
            ->all();
        $this->lastAction = 'export';

        $subject = mb_strtolower($this->email);
        $this->email = '';

        return response()->streamDownload(
            fn (): string => (string) json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ),
            'data-subject-'.md5($subject).'.json',
            ['Content-Type' => 'application/json'],
        );
    }

    public function erase(): void
    {
        $this->validate(['email' => 'required|email']);

        if (! $this->confirmErase) {
            $this->addError('confirmErase', 'Confirm the erasure first — this cannot be undone.');

            return;
        }

        $subject = mb_strtolower($this->email);
        $counts = DataSubjectTool::anonymize($subject);

        ActivityLogger::log('admin', 'delete', null, [
            'tool' => 'data-subject',
            'subject' => $subject,
            'leads_anonymized' => $counts['leads'],
            'applications_anonymized' => $counts['applications'],
        ]);

        $this->summary = $counts;
        $this->lastAction = 'erase';
        $this->reset(['email', 'confirmErase']);

        $this->dispatch('notify', tone: 'success', message: "Erased: {$counts['leads']} lead(s), {$counts['applications']} application(s). Financial + audit records untouched.");
    }

    public function render(): View
    {
        $this->authorize('data-subject.manage');

        return view('admin.data-subject');
    }
}
