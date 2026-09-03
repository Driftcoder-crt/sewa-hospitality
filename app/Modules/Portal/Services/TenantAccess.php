<?php

namespace App\Modules\Portal\Services;

use App\Modules\Portal\Enums\DocumentVisibility;
use App\Modules\Portal\Models\PortalDocument;
use App\Modules\Portal\Models\PortalMove;
use App\Modules\Portal\Models\PortalThread;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Tenant-scoped queries (04-client-portal.md §2 + §8 tests): the ONLY
 * place portal list/detail queries are built. Every surface (controller,
 * Livewire island) asks this class for its query so the isolation
 * matrix — "employee A cannot read org B's anything" — has exactly one
 * implementation. Missing authorization is a 404, never a 403: the
 * portal must not confirm the existence of another org's records.
 */
class TenantAccess
{
    public function __construct(private readonly PortalContext $context) {}

    /** The underlying tenancy resolver (role/org access for writers). */
    public function context(): PortalContext
    {
        return $this->context;
    }

    /** Moves visible to the signed-in member (org-wide or own only). */
    public function moves(): Builder
    {
        $query = PortalMove::query()->with(['organization', 'destinationCity']);

        if ($this->context->isOrgWide()) {
            return $query->forOrganization($this->context->organization()->getKey());
        }

        return $query->forEmployee($this->context->user()->getKey());
    }

    /** 404 unless the move belongs to the member's tenant (and them, if employee). */
    public function authorizeMove(string $moveId): PortalMove
    {
        $move = $this->moves()->findOrFail($moveId);

        return $move;
    }

    /** Documents of a move this member may see (visibility matrix applied). */
    public function documentsFor(PortalMove $move): Builder
    {
        $role = $this->context->role();

        return PortalDocument::query()
            ->where('move_record_id', $move->getKey())
            ->where(function (Builder $query) use ($role): void {
                $query->where('visible_to', DocumentVisibility::Both->value);

                if ($role === 'employee') {
                    $query->orWhere('visible_to', DocumentVisibility::Employee->value);
                }

                if (in_array($role, ['manager', 'billing'], true)) {
                    $query->orWhere('visible_to', DocumentVisibility::Manager->value);
                }
            })
            ->with(['media', 'uploader'])
            ->latest();
    }

    /** 404 unless the document is in-tenant AND visible to this member's role. */
    public function authorizeDocument(string $documentId): PortalDocument
    {
        $document = PortalDocument::query()->findOrFail($documentId);
        $role = $this->context->roleIn((string) $document->organization_id);

        // Not our tenant (or membership vanished) — existence stays secret.
        if ($role === null || ! $document->visible_to->visibleTo($role)) {
            throw new NotFoundHttpException('Document not found.');
        }

        // Employees only reach their own move's documents.
        if ($role === 'employee') {
            $own = PortalMove::query()
                ->where('employee_user_id', $this->context->user()->getKey())
                ->where('organization_id', (string) $document->organization_id)
                ->exists();

            if (! $own) {
                throw new NotFoundHttpException('Document not found.');
            }
        }

        return $document;
    }

    /** Threads the member may read (org-wide: all org threads; employee: own moves'). */
    public function threads(): Builder
    {
        $query = PortalThread::query()
            ->where('organization_id', $this->context->organization()->getKey())
            ->with(['move', 'organization']);

        if (! $this->context->isOrgWide()) {
            $query->whereHas('move', fn (Builder $moveQuery) => $moveQuery
                ->where('employee_user_id', $this->context->user()->getKey()));
        }

        return $query->latest();
    }

    public function authorizeThread(string $threadId): PortalThread
    {
        return $this->threads()->findOrFail($threadId);
    }
}
