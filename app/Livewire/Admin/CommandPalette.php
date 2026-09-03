<?php

namespace App\Livewire\Admin;

use App\Modules\Cms\Models\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * ⌘K command palette (04-modules/05-admin-panel.md §2): role-scoped
 * global search over admin destinations + CMS pages (results follow the
 * operator's policies), keyboard-first (arrows/enter/escape), opened
 * from any admin screen via ⌘K / Ctrl+K or the topbar button.
 */
class CommandPalette extends Component
{
    public bool $open = false;

    public string $query = '';

    public int $highlight = 0;

    #[On('open-palette')]
    public function openPalette(): void
    {
        $this->open = true;
        $this->query = '';
        $this->highlight = 0;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function updatedQuery(): void
    {
        $this->highlight = 0;
    }

    #[Computed]
    public function results(): array
    {
        $q = mb_strtolower(trim($this->query));
        $results = [];
        $user = auth()->user();

        // Destinations — each gated by the SAME ability its screen
        // enforces (route middleware + component authorize), so the
        // palette never advertises a surface the operator would bounce
        // off (05-admin-panel §2 "⌘K — role-scoped").
        $destinations = [
            ['Dashboard', 'admin.dashboard', 'Overview', 'access-admin'],
            ['Pages', 'admin.pages', 'Content', 'cms.view'],
            ['Menus', 'admin.menus', 'Content', 'cms.view'],
            ['Redirects', 'admin.redirects', 'Content', 'cms.view'],
            ['Posts', 'admin.posts', 'Editorial', 'blog.view'],
            ['Leads inbox', 'admin.leads', 'Intake', 'leads.view'],
            ['Pipeline', 'admin.pipeline', 'Intake', 'leads.view'],
            ['Newsletter', 'admin.newsletter', 'Intake', 'leads.view'],
            ['Job postings', 'admin.jobs', 'People', 'careers.view'],
            ['Applications', 'admin.applications', 'People', 'careers.view'],
            ['Employees', 'admin.employees', 'People', 'hr.view'],
            ['Testimonials', 'admin.testimonials', 'Trust', 'testimonials.view'],
            ['CSR programmes', 'admin.csr', 'Trust', 'csr.view'],
            ['Moves', 'admin.moves', 'Portal ops', 'portal.view'],
            ['Threads', 'admin.threads', 'Portal ops', 'portal.view'],
            ['Invitations', 'admin.invitations', 'Portal ops', 'portal.view'],
            ['Quotes', 'admin.quotes', 'Billing', 'billing.view'],
            ['Invoices', 'admin.invoices', 'Billing', 'billing.view'],
            ['Payments', 'admin.payments', 'Billing', 'billing.view'],
            ['Organizations', 'admin.organizations', 'Billing', 'billing.view'],
            ['Finance reports', 'admin.finance', 'Billing', 'billing.view'],
            ['Languages & translations', 'admin.i18n', 'I18n', 'i18n.manage'],
            ['AI console', 'admin.ai', 'AI', 'ai.manage'],
            ['Data subject tool', 'admin.privacy.data-subject', 'System', 'data-subject.manage'],
            ['Two-factor setup', 'admin.security', 'System', 'access-admin'],
        ];

        foreach ($destinations as [$label, $route, $group, $ability]) {
            if ($user?->cannot($ability)) {
                continue;
            }

            if ($q === '' || str_contains(mb_strtolower($label), $q)) {
                $results[] = ['group' => $group, 'label' => $label, 'hint' => 'Go to', 'url' => route($route)];
            }
        }

        // Pages — only what the operator may view (policy-scoped search).
        if ($user?->can('viewAny', Page::class)) {
            Page::query()
                ->when($q !== '', fn ($query) => $query->where(function ($query) use ($q): void {
                    $query->where('title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                }))
                ->orderByDesc('updated_at')
                ->limit(6)
                ->get()
                ->each(function (Page $page) use (&$results): void {
                    $results[] = [
                        'group' => 'Pages',
                        'label' => $page->title,
                        'hint' => '/'.$page->slug.' · '.ucfirst($page->status->value),
                        'url' => route('admin.pages.edit', ['page' => $page->getKey()]),
                    ];
                });
        }

        return $results;
    }

    public function render(): View
    {
        return view('admin.command-palette', [
            // results() — Livewire 4 has no resultsProperty() magic; the
            // old naming died with the framework upgrade.
            'results' => $this->results(),
        ]);
    }
}
