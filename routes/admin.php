<?php

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DataSubjectToolScreen;
use App\Livewire\Admin\SecurityBootstrap;
use App\Modules\Ai\Livewire\AiAdmin;
use App\Modules\Billing\Livewire\FinanceReports;
use App\Modules\Billing\Livewire\InvoiceEditor;
use App\Modules\Billing\Livewire\InvoicesTable;
use App\Modules\Billing\Livewire\OrganizationsManager;
use App\Modules\Billing\Livewire\PaymentsReconciliation;
use App\Modules\Billing\Livewire\QuoteEditor;
use App\Modules\Billing\Livewire\QuotesTable;
use App\Modules\Blog\Livewire\PostEditor;
use App\Modules\Blog\Livewire\PostsTable;
use App\Modules\Careers\Livewire\ApplicationsPipeline;
use App\Modules\Careers\Livewire\EmployeesManager;
use App\Modules\Careers\Livewire\JobEditor;
use App\Modules\Careers\Livewire\JobsTable;
use App\Modules\Cities\Livewire\CitiesTable;
use App\Modules\Cities\Livewire\CityEditor;
use App\Modules\Cities\Livewire\HousingUnitEditor;
use App\Modules\Cities\Livewire\HousingUnitsTable;
use App\Modules\Cms\Http\Controllers\PagePreviewController;
use App\Modules\Cms\Livewire\MenusEditor;
use App\Modules\Cms\Livewire\PageEditor;
use App\Modules\Cms\Livewire\PagesTable;
use App\Modules\Cms\Livewire\RedirectsManager;
use App\Modules\Csr\Livewire\CsrManager;
use App\Modules\I18n\Livewire\I18nManager;
use App\Modules\Leads\Livewire\LeadDetail;
use App\Modules\Leads\Livewire\LeadsInbox;
use App\Modules\Leads\Livewire\NewsletterManager;
use App\Modules\Leads\Livewire\PipelineKanban;
use App\Modules\Portal\Livewire\InvitationsManager;
use App\Modules\Portal\Livewire\MoveEditor;
use App\Modules\Portal\Livewire\MovesTable;
use App\Modules\Portal\Livewire\ThreadsInbox;
use App\Modules\Services\Livewire\ServiceEditor;
use App\Modules\Services\Livewire\ServicesTable;
use App\Modules\Testimonials\Livewire\TestimonialsManager;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin panel — admin.sewahospitality.com (area: admin)
|--------------------------------------------------------------------------
| Custom Livewire 4 panel (no Filament — locked decision). Every admin
| route requires an authenticated staff session; 2FA enforcement and the
| 2h idle timeout are applied via middleware aliases (see bootstrap/app.php).
|
| M0: login (Fortify at /login), dashboard shell, 2FA bootstrap page.
| M1+: the ~30 screens specced in 04-modules/05-admin-panel.md land here
| module by module.
*/

Route::middleware(['auth', 'admin.fresh-session', 'admin.2fa', 'can:access-admin'])
    ->group(function (): void {
        Route::get('/', Dashboard::class)
            ->name('admin.dashboard');

        // Bootstrap helper until the full System screens land (M1):
        // documents how to enable TOTP for the signed-in account.
        Route::get('/security/2fa', SecurityBootstrap::class)
            ->name('admin.security');

        /*
        | Content group (04-modules/05-admin-panel.md §3, Content row) —
        | M1 ships Pages + Menus + Redirects; Media + Settings land in
        | M2. Policies inside the components enforce editor+/admin+.
        */
        Route::get('/pages', PagesTable::class)
            ->name('admin.pages');
        Route::get('/pages/{page}/edit', PageEditor::class)
            ->name('admin.pages.edit');
        Route::get('/pages/{page}/preview', PagePreviewController::class)
            ->name('admin.pages.preview');
        Route::get('/menus', MenusEditor::class)
            ->name('admin.menus');
        Route::get('/redirects', RedirectsManager::class)
            ->name('admin.redirects');

        /*
        | Services group (04-modules/02-services-module.md §4) — M2:
        | tree + editor; coverage editor rides inside the service editor.
        */
        Route::get('/services', ServicesTable::class)
            ->name('admin.services');
        Route::get('/services/{service}/edit', ServiceEditor::class)
            ->name('admin.services.edit');

        /*
        | Cities & Housing group (04-modules/10-cities-content.md §4) — M2.
        */
        Route::get('/cities', CitiesTable::class)
            ->name('admin.cities');
        Route::get('/cities/{city}/edit', CityEditor::class)
            ->name('admin.cities.edit');
        Route::get('/housing-units', HousingUnitsTable::class)
            ->name('admin.housing');
        Route::get('/housing-units/{unit}/edit', HousingUnitEditor::class)
            ->name('admin.housing.edit');

        /*
        | Intake group (04-modules/03-leads-crm.md §4) — M3: inbox +
        | detail + pipeline + newsletter. Policies inside the components
        | enforce leads.view/pii.view/update/assign/export.
        */
        Route::get('/leads', LeadsInbox::class)
            ->name('admin.leads');
        Route::get('/leads/{lead}', LeadDetail::class)
            ->name('admin.leads.show');
        Route::get('/pipeline', PipelineKanban::class)
            ->name('admin.pipeline');
        Route::get('/newsletter', NewsletterManager::class)
            ->name('admin.newsletter');

        /*
        | People group (04-modules/06-hr-employee-module.md §4) — M3:
        | job postings, ATS pipeline, employees, author profiles.
        */
        Route::get('/jobs', JobsTable::class)
            ->name('admin.jobs');
        Route::get('/jobs/{job}/edit', JobEditor::class)
            ->name('admin.jobs.edit');
        Route::get('/applications', ApplicationsPipeline::class)
            ->name('admin.applications');
        Route::get('/applications/{application}/resume', [ApplicationsPipeline::class, 'downloadResume'])
            ->name('admin.applications.resume');
        Route::get('/employees', EmployeesManager::class)
            ->name('admin.employees');

        /*
        | Editorial group (04-modules/07-blog-news.md §4) — M4: posts
        | table + editor. The review workflow (submit → approve →
        | publish) is enforced inside PostPublishGate (four-eyes).
        */
        Route::get('/posts', PostsTable::class)
            ->name('admin.posts');
        Route::get('/posts/{post}/edit', PostEditor::class)
            ->name('admin.posts.edit');

        /*
        | Trust group (04-modules/08-testimonials-reviews.md §5 +
        | 09-csr-module.md §5) — M4: testimonial moderation + GBP cache
        | + review requests; CSR partners/stories with the claims
        | ledger. Policies inside the components enforce
        | testimonials.* / csr.*.
        */
        Route::get('/testimonials', TestimonialsManager::class)
            ->name('admin.testimonials');
        Route::get('/csr', CsrManager::class)
            ->name('admin.csr');

        /*
        | Portal ops group (04-modules/04-client-portal.md §4) — M5: moves
        | (stage machine + checklist + documents), consultant threads,
        | org invitations. Policies inside components enforce
        | portal.view/manage + consultant assignment scoping.
        */
        Route::get('/moves', MovesTable::class)
            ->name('admin.moves');
        Route::get('/moves/{move}/edit', MoveEditor::class)
            ->name('admin.moves.edit');
        Route::get('/threads', ThreadsInbox::class)
            ->name('admin.threads');
        Route::get('/invitations', InvitationsManager::class)
            ->name('admin.invitations');

        /*
        | Billing group (04-modules/12-billing-finance.md §4) — M5:
        | quotes builder, invoices (payments/void/send), payments
        | reconciliation, organizations billing profiles, reports.
        | finance+admin per the matrix (billing.view/manage).
        */
        Route::get('/quotes', QuotesTable::class)
            ->name('admin.quotes');
        Route::get('/quotes/create', QuoteEditor::class)
            ->name('admin.quotes.create');
        Route::get('/quotes/{quote}/edit', QuoteEditor::class)
            ->name('admin.quotes.edit');
        Route::get('/invoices', InvoicesTable::class)
            ->name('admin.invoices');
        Route::get('/invoices/create', InvoiceEditor::class)
            ->name('admin.invoices.create');
        Route::get('/invoices/{invoice}/edit', InvoiceEditor::class)
            ->name('admin.invoices.edit');
        Route::get('/payments', PaymentsReconciliation::class)
            ->name('admin.payments');
        Route::get('/organizations', OrganizationsManager::class)
            ->name('admin.organizations');
        Route::get('/reports/finance', FinanceReports::class)
            ->name('admin.finance');

        /*
        | I18n group (04-modules/11-multilingual.md §6) — M6: locales,
        | UI-string review queue, content machine-draft queue.
        | editor+ per i18n.manage (inside the component).
        */
        Route::get('/i18n', I18nManager::class)
            ->name('admin.i18n');

        /*
        | AI group (08-ai-system/01-ai-architecture.md §6) — M6: provider
        | status, budget gauges, invocation ledger, kill switches, test
        | console. admin+ per ai.manage (inside the component).
        */
        Route::get('/ai', AiAdmin::class)
            ->name('admin.ai');

        /*
        | System group — data-subject tool (05-security-reliability §1.4,
        | DPDP right to access + erasure). admin+ per data-subject.manage.
        */
        Route::get('/privacy/data-subject', DataSubjectToolScreen::class)
            ->name('admin.privacy.data-subject');
    });
