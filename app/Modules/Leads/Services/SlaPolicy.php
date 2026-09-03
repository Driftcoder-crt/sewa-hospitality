<?php

namespace App\Modules\Leads\Services;

use App\Modules\Leads\Enums\LeadType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * SLA clock (04-modules/03-leads-crm.md §4.4): published promises are
 * contact 2 business hours, quote 4, callback 2 — measured in BUSINESS
 * HOURS so a lead arriving Friday 20:00 IST is not "breached" at 22:00.
 *
 * Business-hours calendar (Asia/Kolkata): Mon–Sat, 09:00–19:00 IST;
 * Sunday + outside-window time does not tick. The window is a class
 * constant on purpose — when the ops rhythm changes office hours, this
 * is the single place to edit (and the published copy must follow).
 */
final class SlaPolicy
{
    /** Business window: Mon(1)–Sat(6), 09:00–19:00 IST. */
    public const int OPEN_DAY_START = 1;

    public const int OPEN_DAY_END = 6;

    public const int OPEN_HOUR_START = 9;

    public const int OPEN_HOUR_END = 19;

    public const string TIMEZONE = 'Asia/Kolkata';

    /** Published SLA promise per lead type (hours). */
    private const array HOURS = [
        LeadType::Enquiry->value => 2.0,
        LeadType::Callback->value => 2.0,
        LeadType::QuoteRequest->value => 4.0,
        LeadType::Demo->value => 4.0,
        LeadType::Newsletter->value => 0.0, // newsletters are not SLA-tracked
    ];

    /** Due time for a lead of $type created at $from (defaults now). */
    public static function dueFor(LeadType $type, ?CarbonInterface $from = null): ?CarbonImmutable
    {
        $hours = self::HOURS[$type->value] ?? 0.0;

        if ($hours <= 0.0) {
            return null;
        }

        return self::addBusinessHours($from ?? now(), $hours);
    }

    /** Add $hours of BUSINESS time to $from (Sunday/outside-window skipped). */
    public static function addBusinessHours(CarbonInterface $from, float $hours): CarbonImmutable
    {
        $cursor = CarbonImmutable::instance($from)->timezone(self::TIMEZONE);
        $remaining = $hours;
        $guard = 0;

        while ($remaining > 0 && $guard++ < 24 * 30) {
            // Jump to the next business-hour instant if we're outside it.
            if (! self::isBusinessHour($cursor)) {
                $cursor = self::nextBusinessStart($cursor);

                continue;
            }

            // End of the current business hour.
            $hourEnd = $cursor->startOfHour()->addHour();

            // Fraction of this hour that is bookable.
            $spanMinutes = (int) ceil($cursor->diffInMinutes($hourEnd));
            $consumable = min($remaining, $spanMinutes / 60);

            if ($consumable >= $remaining) {
                return $cursor->addMinutes((int) ceil($remaining * 60));
            }

            $remaining -= $consumable;
            $cursor = $hourEnd;
        }

        return $cursor;
    }

    private static function isBusinessHour(CarbonInterface $at): bool
    {
        $dow = (int) $at->dayOfWeekIso;
        $hour = (float) $at->format('G');

        return $dow >= self::OPEN_DAY_START
            && $dow <= self::OPEN_DAY_END
            && $hour >= self::OPEN_HOUR_START
            && $hour < self::OPEN_HOUR_END;
    }

    private static function nextBusinessStart(CarbonInterface $at): CarbonImmutable
    {
        $cursor = CarbonImmutable::instance($at)->timezone(self::TIMEZONE);

        do {
            if ((int) $cursor->dayOfWeekIso > self::OPEN_DAY_END || (int) $cursor->dayOfWeekIso < self::OPEN_DAY_START) {
                // Sunday (or wrap) → next day 09:00.
                $cursor = $cursor->addDay()->setTime(self::OPEN_HOUR_START, 0);

                continue;
            }

            if ((float) $cursor->format('G') < self::OPEN_HOUR_START) {
                $cursor = $cursor->setTime(self::OPEN_HOUR_START, 0);

                continue;
            }

            if ((float) $cursor->format('G') >= self::OPEN_HOUR_END) {
                $cursor = $cursor->addDay()->setTime(self::OPEN_HOUR_START, 0);

                continue;
            }

            break;
        } while (true);

        return $cursor;
    }
}
