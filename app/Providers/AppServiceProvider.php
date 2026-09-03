<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Livewire\Admin\DataSubjectToolScreen;
use App\Models\User;
use App\Modules\Ai\Jobs\LeadEnrich;
use App\Modules\Ai\Livewire\AiAdmin;
use App\Modules\Ai\Services\TranslationDispatcher;
use App\Modules\Billing\Events\InvoiceIssued;
use App\Modules\Billing\Events\PaymentRecorded;
use App\Modules\Billing\Events\QuoteAccepted;
use App\Modules\Billing\Listeners\ConvertAcceptedQuote;
use App\Modules\Billing\Listeners\NotifyPaymentRecorded;
use App\Modules\Billing\Listeners\SendInvoiceIssuedMail;
use App\Modules\Billing\Livewire\FinanceReports;
use App\Modules\Billing\Livewire\InvoiceEditor;
use App\Modules\Billing\Livewire\InvoicesTable;
use App\Modules\Billing\Livewire\OrganizationsManager;
use App\Modules\Billing\Livewire\PaymentsReconciliation;
use App\Modules\Billing\Livewire\QuoteEditor;
use App\Modules\Billing\Livewire\QuotesTable;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\InvoicePayment;
use App\Modules\Billing\Models\Quote;
use App\Modules\Billing\Policies\InvoicePaymentPolicy;
use App\Modules\Billing\Policies\InvoicePolicy;
use App\Modules\Billing\Policies\OrganizationPolicy;
use App\Modules\Billing\Policies\QuotePolicy;
use App\Modules\Blog\Livewire\PostEditor;
use App\Modules\Blog\Livewire\PostsTable;
use App\Modules\Blog\Models\Post;
use App\Modules\Blog\Policies\PostPolicy;
use App\Modules\Careers\Events\ApplicationReceived;
use App\Modules\Careers\Events\ApplicationStatusChanged;
use App\Modules\Careers\Listeners\SendApplicationNotifications;
use App\Modules\Careers\Listeners\SendApplicationStatusEmail;
use App\Modules\Careers\Livewire\ApplicationForm;
use App\Modules\Careers\Models\Employee;
use App\Modules\Careers\Models\JobApplication;
use App\Modules\Careers\Models\JobPosting;
use App\Modules\Careers\Policies\EmployeePolicy;
use App\Modules\Careers\Policies\JobApplicationPolicy;
use App\Modules\Careers\Policies\JobPostingPolicy;
use App\Modules\Cities\Events\CityPublished;
use App\Modules\Cities\Models\City;
use App\Modules\Cities\Models\HousingUnit;
use App\Modules\Cities\Policies\CityPolicy;
use App\Modules\Cities\Policies\HousingUnitPolicy;
use App\Modules\Cms\Events\PagePublished;
use App\Modules\Cms\Events\PageUnpublished;
use App\Modules\Cms\Models\MenuItem;
use App\Modules\Cms\Models\Page;
use App\Modules\Cms\Models\Redirect;
use App\Modules\Cms\Policies\MenuItemPolicy;
use App\Modules\Cms\Policies\PagePolicy;
use App\Modules\Cms\Policies\RedirectPolicy;
use App\Modules\Csr\Livewire\CsrManager;
use App\Modules\Csr\Models\CsrStory;
use App\Modules\Csr\Models\NgoPartner;
use App\Modules\Csr\Policies\NgoPartnerPolicy;
use App\Modules\I18n\Livewire\I18nManager;
use App\Modules\Leads\Events\LeadCreated;
use App\Modules\Leads\Events\SlaBreached;
use App\Modules\Leads\Listeners\AlertSlaBreach;
use App\Modules\Leads\Listeners\SendLeadNotifications;
use App\Modules\Leads\Livewire\CallbackForm;
use App\Modules\Leads\Livewire\ContactForm;
use App\Modules\Leads\Livewire\NewsletterSignup;
use App\Modules\Leads\Livewire\QuoteForm;
use App\Modules\Leads\Models\Lead;
use App\Modules\Leads\Policies\LeadPolicy;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portal\Events\ChecklistItemDone;
use App\Modules\Portal\Events\DocumentPublished;
use App\Modules\Portal\Events\MessageSent;
use App\Modules\Portal\Events\MoveStageChanged;
use App\Modules\Portal\Listeners\NotifyChecklistDone;
use App\Modules\Portal\Listeners\NotifyDocumentPublished;
use App\Modules\Portal\Listeners\NotifyMessageSent;
use App\Modules\Portal\Listeners\NotifyMoveStageChange;
use App\Modules\Portal\Livewire\InvitationsManager;
use App\Modules\Portal\Livewire\MoveEditor;
use App\Modules\Portal\Livewire\MovesTable;
use App\Modules\Portal\Livewire\ThreadsInbox;
use App\Modules\Portal\Models\PortalDocument;
use App\Modules\Portal\Models\PortalMove;
use App\Modules\Portal\Models\PortalNotification;
use App\Modules\Portal\Models\PortalThread;
use App\Modules\Portal\Policies\PortalDocumentPolicy;
use App\Modules\Portal\Policies\PortalMovePolicy;
use App\Modules\Portal\Policies\PortalThreadPolicy;
use App\Modules\Services\Events\ServicePublished;
use App\Modules\Services\Models\Service;
use App\Modules\Services\Policies\ServicePolicy;
use App\Modules\Testimonials\Livewire\TestimonialsManager;
use App\Modules\Testimonials\Models\Testimonial;
use App\Modules\Testimonials\Policies\TestimonialPolicy;
use App\Support\Analytics\Jobs\ReportConversion;
use App\Support\Seo\RegenerateSitemap;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Staff roles (config/sewa.php) may enter the admin surface;
     * client roles (client-manager, client-employee) may enter the portal.
     */
    public function boot(): void
    {
        // Dev-only integrity guards (error-locks doctrine: fail loudly).
        if (! $this->app->isProduction()) {
            Model::preventLazyLoading();
            Model::preventSilentlyDiscardingAttributes();
        }

        // Factories live FLAT in Database\Factories (e.g. LeadFactory)
        // while models are module-namespaced (App\Modules\...\Models\Lead).
        // Bridge the two so X::factory() resolves for every module model.
        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Database\\Factories\\'.class_basename($modelName).'Factory',
        );

        Password::defaults(function (): ?Password {
            return $this->app->isProduction()
                ? Password::min(12)->letters()->mixedCase()->numbers()->symbols()
                : null;
        });

        // Spatie super-admin bypass; every other check walks the matrix.
        Gate::before(fn (User $user, string $ability): ?bool => $user->hasRole('super-admin') ? true : null);

        Gate::define('access-admin', fn (User $user): bool => $user->hasAnyRole(config('sewa.staff_roles')));
        Gate::define('access-portal', fn (User $user): bool => $user->hasAnyRole(['client-manager', 'client-employee']));

        // I18n (04-modules/11-multilingual.md §6) is editorial work —
        // editor+; the AI console (08-ai-system/01 §6) is admin+ (the
        // test console invokes paid infrastructure).
        Gate::define('i18n.manage', fn (User $user): bool => $user->hasAnyRole(['admin', 'editor']));
        Gate::define('ai.manage', fn (User $user): bool => $user->hasAnyRole(['admin']));
        Gate::define('data-subject.manage', fn (User $user): bool => $user->hasAnyRole(['admin']));

        // Model policies (module namespaces register explicitly).
        Gate::policy(Page::class, PagePolicy::class);
        Gate::policy(MenuItem::class, MenuItemPolicy::class);
        Gate::policy(Redirect::class, RedirectPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(City::class, CityPolicy::class);
        Gate::policy(HousingUnit::class, HousingUnitPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(JobPosting::class, JobPostingPolicy::class);
        Gate::policy(JobApplication::class, JobApplicationPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(Testimonial::class, TestimonialPolicy::class);
        Gate::policy(NgoPartner::class, NgoPartnerPolicy::class);
        // The CSR policy unions partner + story subjects; without this
        // row CsrStory had NO policy at all — every authorize() on a
        // story denied (publish/unpublish silently aborted in the UI).
        Gate::policy(CsrStory::class, NgoPartnerPolicy::class);
        Gate::policy(PortalMove::class, PortalMovePolicy::class);
        Gate::policy(PortalDocument::class, PortalDocumentPolicy::class);
        Gate::policy(PortalThread::class, PortalThreadPolicy::class);
        Gate::policy(Quote::class, QuotePolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(InvoicePayment::class, InvoicePaymentPolicy::class);
        Gate::policy(Organization::class, OrganizationPolicy::class);

        // Publish → sitemap regeneration (queued on `syncs`).
        Event::listen(PagePublished::class, RegenerateSitemap::class);
        Event::listen(PageUnpublished::class, RegenerateSitemap::class);
        Event::listen(ServicePublished::class, RegenerateSitemap::class);
        Event::listen(CityPublished::class, RegenerateSitemap::class);

        /*
         | Translation pipeline fan-out (04-modules/11-multilingual.md §4
         | step 2): published EN content enqueues TranslateContent per
         | enabled auto-translate locale (queue `ai`). Kill switch →
         | nothing enqueued, content publishes EN-only (nothing blocks).
         */
        Event::listen(PagePublished::class, function (PagePublished $event): void {
            TranslationDispatcher::forEntity($event->page);
        });
        Event::listen(ServicePublished::class, function (ServicePublished $event): void {
            TranslationDispatcher::forEntity($event->service);
        });
        Event::listen(CityPublished::class, function (CityPublished $event): void {
            TranslationDispatcher::forEntity($event->city);
        });

        /*
         | Lead enrichment (08-ai-system/02 §3): advisory panel only —
         | queued on `ai`, kill-switch guarded, lead flow never waits.
         */
        Event::listen(LeadCreated::class, function (LeadCreated $event): void {
            LeadEnrich::dispatch($event->lead->getKey());
        });

        /*
         | Server-confirmed conversions (02-analytics-plan §1.2): the
         | money events fire from the DB write — consent-checked,
         | PII-free, queued on `syncs`, breaker-guarded.
         */
        Event::listen(LeadCreated::class, function (LeadCreated $event): void {
            if ($job = ReportConversion::forLead($event->lead)) {
                dispatch($job);
            }
        });
        Event::listen(ApplicationReceived::class, function (ApplicationReceived $event): void {
            if ($job = ReportConversion::forApplication($event->application)) {
                dispatch($job);
            }
        });

        /*
         | Money-path events (04-modules/03-leads-crm.md §7 + 06-hr §7):
         | queued listeners on `emails` — a mail outage never loses a
         | lead, and the request never waits on SMTP.
         */
        Event::listen(LeadCreated::class, SendLeadNotifications::class);
        Event::listen(SlaBreached::class, AlertSlaBreach::class);
        Event::listen(ApplicationReceived::class, SendApplicationNotifications::class);
        Event::listen(ApplicationStatusChanged::class, SendApplicationStatusEmail::class);

        /*
         | Portal + billing events (04-client-portal §7 + 12-billing §7):
         | all listeners queued — the stage machine, document publish,
         | chat and payment paths never wait on mail or notifications.
         */
        Event::listen(MoveStageChanged::class, NotifyMoveStageChange::class);
        Event::listen(ChecklistItemDone::class, NotifyChecklistDone::class);
        Event::listen(MessageSent::class, NotifyMessageSent::class);
        Event::listen(DocumentPublished::class, NotifyDocumentPublished::class);
        Event::listen(InvoiceIssued::class, SendInvoiceIssuedMail::class);
        Event::listen(PaymentRecorded::class, NotifyPaymentRecorded::class);
        Event::listen(QuoteAccepted::class, ConvertAcceptedQuote::class);

        /*
         | Portal layout badge: unread notification count for the header
         | (04 doc §3 — notification center, poll 30s native transport).
         */
        \Illuminate\Support\Facades\View::composer('layouts.portal', static function (View $view): void {
            $unread = 0;

            if (auth()->check()) {
                $unread = PortalNotification::query()
                    ->forUser((string) auth()->id())
                    ->unread()
                    ->count();
            }

            $view->with('unreadCount', $unread);
        });

        /*
         | Module Livewire aliases — the default <livewire:leads.x> tag
         | mapping resolves against App\Livewire\... only; module
         | components register explicitly (04-modules/00-module-system.md).
         */
        Livewire::component('leads.contact-form', ContactForm::class);
        Livewire::component('leads.quote-form', QuoteForm::class);
        Livewire::component('leads.callback-form', CallbackForm::class);
        Livewire::component('leads.newsletter-signup', NewsletterSignup::class);
        Livewire::component('careers.application-form', ApplicationForm::class);
        Livewire::component('blog.posts-table', PostsTable::class);
        Livewire::component('blog.post-editor', PostEditor::class);
        Livewire::component('testimonials.manager', TestimonialsManager::class);
        Livewire::component('csr.manager', CsrManager::class);
        Livewire::component('portal.moves-table', MovesTable::class);
        Livewire::component('portal.move-editor', MoveEditor::class);
        Livewire::component('portal.threads-inbox', ThreadsInbox::class);
        Livewire::component('portal.invitations-manager', InvitationsManager::class);
        Livewire::component('billing.quotes-table', QuotesTable::class);
        Livewire::component('billing.quote-editor', QuoteEditor::class);
        Livewire::component('billing.invoices-table', InvoicesTable::class);
        Livewire::component('billing.invoice-editor', InvoiceEditor::class);
        Livewire::component('billing.payments-reconciliation', PaymentsReconciliation::class);
        Livewire::component('billing.organizations-manager', OrganizationsManager::class);
        Livewire::component('billing.finance-reports', FinanceReports::class);
        Livewire::component('i18n.manager', I18nManager::class);
        Livewire::component('ai.admin', AiAdmin::class);
        Livewire::component('admin.data-subject', DataSubjectToolScreen::class);

        $this->registerRateLimiters();
        $this->registerFortifyViews();
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by(
            ($request->input('email', '').'|'.$request->ip()),
        ));

        // Public form writes: 5/min/IP (error lock #3; 20/h is layered on
        // the lead/application controllers in M3).
        RateLimiter::for('public-writes', fn (Request $request): Limit => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('search', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));

        RateLimiter::for('api', fn (Request $request): Limit => $request->user()
            ? Limit::perMinute(60)->by('api-user:'.$request->user()->getAuthIdentifier())
            : Limit::perMinute(60)->by('api-ip:'.$request->ip()));
    }

    private function registerFortifyViews(): void
    {
        // Reset path carries the HIBP breach-list check (05-security-
        // reliability §1.1: k-anonymity on reset — fail-open on outage).
        // Class-name string, not an instance — Fortify binds it via
        // app()->singleton(ResetsUserPasswords::class, $callback), which
        // requires Closure|string as the concrete.
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
    }
}
