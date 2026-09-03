<?php

namespace App\Modules\Portal\Enums;

/** Checklist item state (schema §8): done_at is the timestamp, status the filter. */
enum ChecklistStatus: string
{
    case Pending = 'pending';
    case Done = 'done';
}
