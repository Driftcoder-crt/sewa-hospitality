<?php

namespace App\Modules\Portal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Portal\Models\PortalDocument;
use App\Modules\Portal\Services\TenantAccess;
use App\Support\Audit\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentsController extends Controller
{
    public function __construct(private readonly TenantAccess $access) {}

    /** Document list by category for one move (04 doc §3), role-scoped. */
    public function index(string $move): View
    {
        $move = $this->access->authorizeMove($move);

        $documents = $this->access->documentsFor($move)
            ->when(request('category'), fn ($q, $category) => $q->where('category', $category))
            ->get()
            ->groupBy(fn ($document) => $document->category->value);

        return view('portal.documents.index', [
            'move' => $move,
            'grouped' => $documents,
            'activeCategory' => request('category'),
        ]);
    }

    /**
     * Signed 15-minute download (04 doc §5): the signature carries the
     * authorization; every serve is audit-logged (portal context).
     */
    public function download(string $document): StreamedResponse
    {
        // Signed middleware validated the URL; resolve with tenant checks
        // too (defence in depth — a signature alone never bypasses tenancy).
        $document = $this->access->authorizeDocument($document);

        $media = $document->media;
        $disk = Storage::disk($media?->disk ?? 'local');

        // getPath() is ABSOLUTE (disk root prepended by spatie) — Storage
        // expects disk-RELATIVE paths, so use getPathRelativeToRoot() or
        // every exists()/download() miss and the route 404s.
        abort_if(! $media || ! $disk->exists($media->getPathRelativeToRoot()), 404);

        ActivityLogger::log('portal', 'export', $document, [
            'action' => 'document_download',
            'title' => $document->title,
            'category' => $document->category?->value,
        ]);

        return $disk->download($media->getPathRelativeToRoot(), $this->downloadName($document, $media->file_name));
    }

    /** Human download name: title keeps its extension. */
    private function downloadName(PortalDocument $document, ?string $fileName): string
    {
        $extension = pathinfo((string) $fileName, PATHINFO_EXTENSION);
        $slug = Str::slug($document->title) ?: 'document';

        return $extension !== '' ? "{$slug}.{$extension}" : $slug;
    }
}
