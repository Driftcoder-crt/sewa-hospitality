<?php

namespace App\Modules\Csr\Livewire;

use App\Models\User;
use App\Modules\Ai\Services\TranslationDispatcher;
use App\Modules\Csr\Models\CsrStory;
use App\Modules\Csr\Models\NgoPartner;
use App\Support\Audit\ActivityLogger;
use App\Support\Cms\HtmlSanitizer;
use App\Support\Seo\RegenerateSitemap;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * CSR manager (09 doc §4/§5 + 05-admin-panel.md §3): NGO partners with
 * the claims ledger (one measurable claim + as-of + source) and CSR
 * stories with the optional blog cross-post. Honesty fields are
 * validated hard — a claim without as-of/source can't be saved.
 */
#[Layout('layouts.admin')]
class CsrManager extends Component
{
    /** active tab: partners | stories */
    #[Url]
    public string $tab = 'partners';

    public bool $showForm = false;

    public string $editingId = '';

    // ---- Partner form ----
    public string $pName = '';

    public string $pSlug = '';

    public string $pWebsite = '';

    public string $pDescription = '';

    public string $pClaim = '';

    public string $pClaimAsOf = '';

    public string $pClaimSource = '';

    public string $pSince = '';

    public string $pCity = '';

    public string $pStatus = 'active';

    public string $pFocus = '';

    public int $pSort = 0;

    // ---- Story form ----
    public string $sTitle = '';

    public string $sSlug = '';

    public string $sBody = '';

    public string $sPartnerId = '';

    public bool $sCrossPost = false;

    public string $sAuthorId = '';

    public function createPartner(): void
    {
        $this->authorize('create', NgoPartner::class);
        $this->validatePartner();

        NgoPartner::query()->create($this->partnerPayload());
        ActivityLogger::log('admin', 'create', null, ['partner' => $this->pName]);

        $this->resetPartnerForm();
        $this->dispatch('notify', tone: 'success', message: 'Partner created.');
    }

    public function editPartner(string $id): void
    {
        $partner = NgoPartner::query()->findOrFail($id);
        $this->authorize('update', $partner);

        $this->editingId = $id;
        $this->tab = 'partners';
        $this->showForm = true;
        $this->pName = (string) $partner->name;
        $this->pSlug = (string) $partner->slug;
        $this->pWebsite = (string) ($partner->website ?? '');
        $this->pDescription = (string) ($partner->description ?? '');
        $this->pClaim = (string) ($partner->claim ?? '');
        $this->pClaimAsOf = (string) ($partner->claim_as_of ?? '');
        $this->pClaimSource = (string) ($partner->claim_source ?? '');
        $this->pSince = (string) ($partner->since ?? '');
        $this->pCity = (string) ($partner->city ?? '');
        $this->pStatus = $partner->status->value;
        $this->pFocus = implode(', ', (array) $partner->focus_areas);
        $this->pSort = (int) $partner->sort;
    }

    public function updatePartner(): void
    {
        $partner = NgoPartner::query()->findOrFail($this->editingId);
        $this->authorize('update', $partner);
        $this->validatePartner();

        $partner->update($this->partnerPayload());
        ActivityLogger::log('admin', 'update', $partner, ['name' => $this->pName]);

        $this->resetPartnerForm();
        $this->dispatch('notify', tone: 'success', message: 'Partner updated.');
    }

    public function deletePartner(string $id): void
    {
        $partner = NgoPartner::query()->findOrFail($id);
        $this->authorize('delete', $partner);

        ActivityLogger::log('admin', 'delete', $partner, ['name' => $partner->name]);
        $partner->delete();
        $this->dispatch('notify', tone: 'success', message: 'Partner deleted.');
    }

    public function createStory(): void
    {
        $this->authorize('create', NgoPartner::class); // stories share the CSR policy
        $this->validateStory();

        CsrStory::query()->create([
            ...$this->storyPayload(),
            'author_user_id' => $this->sAuthorId ?: auth()->id(),
        ]);
        ActivityLogger::log('admin', 'create', null, ['story' => $this->sTitle]);

        $this->resetStoryForm();
        $this->dispatch('notify', tone: 'success', message: 'Story created as draft.');
    }

    public function editStory(string $id): void
    {
        $story = CsrStory::query()->findOrFail($id);
        $this->authorize('view', $story);

        $this->editingId = $id;
        $this->tab = 'stories';
        $this->showForm = true;
        $this->sTitle = (string) $story->title;
        $this->sSlug = (string) $story->slug;
        $this->sBody = (string) $story->body;
        $this->sPartnerId = (string) $story->ngo_partner_id;
        $this->sCrossPost = (bool) $story->cross_post_to_blog;
        $this->sAuthorId = (string) $story->author_user_id;
    }

    public function updateStory(): void
    {
        $story = CsrStory::query()->findOrFail($this->editingId);
        $this->authorize('update', $story);
        $this->validateStory();

        $story->update($this->storyPayload());
        ActivityLogger::log('admin', 'update', $story, ['title' => $this->sTitle]);

        $this->resetStoryForm();
        $this->dispatch('notify', tone: 'success', message: 'Story updated.');
    }

    /** Publish or unpublish a story (published_at stamped on publish). */
    public function toggleStoryPublish(string $id): void
    {
        $story = CsrStory::query()->findOrFail($id);
        $this->authorize('update', $story);

        if ($story->status === 'published') {
            $story->update(['status' => 'draft', 'published_at' => null]);
        } else {
            if (trim((string) $story->body) === '') {
                $this->dispatch('notify', tone: 'error', message: 'Publish blocked: story body is empty.');

                return;
            }
            $story->update(['status' => 'published', 'published_at' => $story->published_at ?? now()]);

            // Translation fan-out (11-multilingual §4): a published EN
            // story enqueues machine-draft jobs per enabled auto-translate
            // locale (queue `ai`) — same contract as the page/service/city/
            // post publish paths. Kill switch → nothing enqueued.
            TranslationDispatcher::forEntity($story->fresh());
        }

        ActivityLogger::log('admin', 'toggle-publish', $story, ['status' => $story->status]);
        RegenerateSitemap::dispatch();
        $this->dispatch('notify', tone: 'success', message: 'Story '.$story->status.'.');
    }

    public function render(): View
    {
        $this->authorize('viewAny', NgoPartner::class);

        return view('csr.livewire.csr-manager', [
            'partners' => NgoPartner::query()->withCount('stories')->orderBy('sort')->orderBy('name')->get(),
            'stories' => CsrStory::query()->with('partner:id,name')->orderByDesc('created_at')->limit(50)->get(),
            'authors' => User::query()->role('author')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /* ── Private helpers ────────────────────────────────────────────── */

    private function validatePartner(): void
    {
        $this->validate([
            'pName' => ['required', 'string', 'max:190'],
            // Optional — payload() derives it from pName when blank.
            'pSlug' => ['nullable', 'alpha_dash', 'max:190', Rule::unique('ngo_partners', 'slug')->ignore($this->editingId ?: null)],
            'pWebsite' => ['nullable', 'url:http,https', 'max:300'],
            'pDescription' => ['nullable', 'string', 'max:5000'],
            // Claims ledger integrity (09 doc §4.1): claim, as-of and
            // source stand or fall together — no naked numbers.
            'pClaim' => ['nullable', 'string', 'max:300'],
            'pClaimAsOf' => ['nullable', 'string', 'max:40', 'required_with:pClaim'],
            'pClaimSource' => ['nullable', 'string', 'max:300', 'required_with:pClaim'],
            'pSince' => ['nullable', 'integer', 'min:1990', 'max:'.(now()->year)],
            'pStatus' => ['required', 'in:active,archived'],
            'pFocus' => ['nullable', 'string', 'max:500'],
            'pSort' => ['integer', 'min:0', 'max:999'],
        ]);
    }

    private function partnerPayload(): array
    {
        return [
            'name' => $this->pName,
            'slug' => Str::slug($this->pSlug ?: $this->pName),
            'website' => $this->pWebsite ?: null,
            'description' => $this->pDescription ?: null,
            'claim' => $this->pClaim ?: null,
            'claim_as_of' => $this->pClaimAsOf ?: null,
            'claim_source' => $this->pClaimSource ?: null,
            'since' => $this->pSince !== '' ? (int) $this->pSince : null,
            'city' => $this->pCity ?: null,
            'status' => $this->pStatus,
            'focus_areas' => collect(explode(',', $this->pFocus))->map(fn ($f) => trim($f))->filter()->values()->all(),
            'sort' => $this->pSort,
        ];
    }

    private function validateStory(): void
    {
        $this->validate([
            'sTitle' => ['required', 'string', 'max:190'],
            // Optional — payload() derives it from sTitle when blank
            // (same contract as pSlug on the partner form).
            'sSlug' => ['nullable', 'alpha_dash', 'max:190', Rule::unique('csr_stories', 'slug')->ignore($this->editingId ?: null)],
            'sBody' => ['required', 'string', 'max:60000'],
            'sPartnerId' => ['nullable', 'exists:ngo_partners,id'],
            'sAuthorId' => ['nullable', 'exists:users,id'],
        ]);
    }

    private function storyPayload(): array
    {
        return [
            'title' => $this->sTitle,
            'slug' => Str::slug($this->sSlug ?: $this->sTitle),
            'body' => HtmlSanitizer::clean($this->sBody),
            'ngo_partner_id' => $this->sPartnerId ?: null,
            'cross_post_to_blog' => $this->sCrossPost,
            'status' => 'draft',
        ];
    }

    private function resetPartnerForm(): void
    {
        $this->reset(['showForm', 'editingId', 'pName', 'pSlug', 'pWebsite', 'pDescription', 'pClaim', 'pClaimAsOf', 'pClaimSource', 'pSince', 'pCity', 'pFocus', 'pSort']);
        $this->pStatus = 'active';
    }

    private function resetStoryForm(): void
    {
        $this->reset(['showForm', 'editingId', 'sTitle', 'sSlug', 'sBody', 'sPartnerId', 'sCrossPost', 'sAuthorId']);
    }
}
