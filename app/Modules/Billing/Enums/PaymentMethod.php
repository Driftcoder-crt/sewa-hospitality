<?php

namespace App\Modules\Billing\Enums;

/** Payment recording method (schema §9). */
enum PaymentMethod: string
{
    case Bank = 'bank';
    case Upi = 'upi';
    case Cheque = 'cheque';
    case Gateway = 'gateway';

    public function label(): string
    {
        return match ($this) {
            self::Bank => 'Bank transfer',
            self::Upi => 'UPI',
            self::Cheque => 'Cheque',
            self::Gateway => 'Gateway',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
