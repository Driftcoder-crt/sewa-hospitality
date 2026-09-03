<?php

namespace App\Modules\Testimonials\Livewire;

use App\Modules\Cities\Models\City;
use App\Modules\Services\Models\Service;
use App\Modules\Testimonials\Enums\TestimonialStatus;
use App\Modules\Testimonials\Models\GoogleReview;
use App\Modules\Testimonials\Models\ReviewRequest;
use App\Modules\Testimonials\Models\Testimonial;
use App\Modules\Testimonials\Services\GbpSyncService;
use App\Support\Audit\ActivityLogger;
use App\Support\Seo\RegenerateSitemap;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Testimonials manager (08 doc §5 + 05-admin-panel.md §3): moderation
 * queue (consent gate + honesty rule), the GBP sync cache with the
 * manual re-sync, and the review-request queue with the one-per-move
 * invariant surfaced. Verified badges are NEVER hand-set — they come
 * from a linked Google review or a completed move.
 */
#[Layout('layouts.admin')]
class TestimonialsManager extends Component
{
    use WithPagination;

    /** active tab: moderation | google | requests */
    #[Url]
    public string $tab = 'moderation';

    #[Url]
    public string $status = '';

    #[Url]
    public string $q = '';

    public function syncGbp(): void
    {
        $this->authorize('update', Testimonial::class);

        // Manual re-sync rides the same breaker + honesty rules as the
        // 06:00 cron (SyncGoogleReviews) — never writes when unconfigured.
        $result = app(GbpSyncService::class)->sync();
        ActivityLogger::log('admin', 'gbp-sync', null, $result);

        $this->dispatch('notify', tone: 'success', message: 'GBP sync finished: '.($result['skipped'] ? 'skipped (connector unconfigured)' : $result['imported'].' imported, '.$result['recovered'].' recovery alerts.'));
    }

    /** Publish — through the consent + honesty gate. */
    public function publish(string $id): void
    {
        $testimonial = Testimonial::query()->findOrFail($id);
        $this->authorize('update', $testimonial);

        if (trim((string) $testimonial->body) === '' || $testimonial->rating === null) {
            $this->dispatch('notify', tone: 'error', message: 'Publish blocked: body and rating are required (rating-honesty rule).');

            return;
        }

        $testimonial->forceFill([
            'status' => TestimonialStatus::Published,
            'published_at' => now(),
        ])->save();

        ActivityLogger::log('admin', 'publish', $testimonial, ['name' => $testimonial->displayName()]);
        RegenerateSitemap::dispatch();
        $this->dispatch('notify', tone: 'success', message: 'Testimonial published.');
    }

    public function archive(string $id): void
    {
        $testimonial = Testimonial::query()->findOrFail($id);
        $this->authorize('update', $testimonial);

        $testimonial->forceFill(['status' => TestimonialStatus::Archived])->save();
        ActivityLogger::log('admin', 'archive', $testimonial);
        RegenerateSitemap::dispatch();
        $this->dispatch('notify', tone: 'success', message: 'Testimonial archived.');
    }

    /** Toggle the named-consent gate (08 doc §5: consent-gated names). */
    public function toggleConsent(string $id): void
    {
        $testimonial = Testimonial::query()->findOrFail($id);
        $this->authorize('update', $testimonial);

        $testimonial->forceFill(['consent_named' => ! $testimonial->consent_named])->save();
        ActivityLogger::log('admin', 'update', $testimonial, ['consent_named' => $testimonial->consent_named]);
        $this->dispatch('notify', tone: 'success', message: $testimonial->consent_named ? 'Named display consented.' : 'Displaying first name + city.');
    }

    public function render(): View
    {
        $this->authorize('viewAny', Testimonial::class);

        return view('testimonials.livewire.testimonials-manager', [
            'testimonials' => $this->moderationQuery()->paginate(10, ['*'], 'moderationPage'),
            'googleReviews' => $this->googleQuery()->paginate(10, ['*'], 'googlePage'),
            'requests' => $this->requestsQuery()->paginate(10, ['*'], 'requestsPage'),
            'statuses' => TestimonialStatus::options(),
            'stats' => app(GbpSyncService::class)->stats(),
            'queue' => [
                'pending' => Testimonial::query()->where('status', TestimonialStatus::Pending)->count(),
                'google' => GoogleReview::query()->count(),
                'requests' => ReviewRequest::query()->where('status', 'queued')->count(),
                'done' => ReviewRequest::query()->where('status', 'done')->count(),
            ],
            'cityNames' => City::query()->orderBy('name')->pluck('name', 'id'),
            'serviceNames' => Service::query()->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    private function moderationQuery(): Builder
    {
        return Testimonial::query()
            ->with(['city:id,name', 'service:id,name', 'googleReview:id,external_id,rating'])
            ->when($this->tab === 'moderation' && $this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->tab === 'moderation' && $this->q !== '', fn ($q) => $q->where('client_name', 'like', '%'.$this->q.'%'))
            ->when($this->tab !== 'moderation', fn ($q) => $q->where('status', TestimonialStatus::Published))
            ->orderByRaw("case status when 'pending' then 0 else 1 end")
            ->orderByDesc('created_at');
    }

    private function googleQuery(): Builder
    {
        return GoogleReview::query()->orderByDesc('review_at');
    }

    private function requestsQuery(): Builder
    {
        return ReviewRequest::query()
            ->when($this->q !== '' && $this->tab === 'requests', fn ($q) => $q->where('move_reference', 'like', '%'.$this->q.'%'))
            ->orderByRaw("case status when 'queued' then 0 when 'sent' then 1 when 'followed_up' then 2 else 3 end")
            ->orderByDesc('created_at');
    }
}
