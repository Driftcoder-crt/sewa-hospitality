<?php

namespace App\Modules\Portal\Enums;

/** Message author type (schema §8): system messages carry no user. */
enum SenderRole: string
{
    case Client = 'client';
    case Consultant = 'consultant';
    case System = 'system';
}
