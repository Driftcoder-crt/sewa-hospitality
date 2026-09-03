<?php

namespace App\Modules\Portal\Events;

use App\Modules\Portal\Models\PortalDocument;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Publish action → employee notified (04 doc §4.3). */
class DocumentPublished
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly PortalDocument $document) {}
}
