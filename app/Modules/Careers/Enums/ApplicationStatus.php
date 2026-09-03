<?php

namespace App\Modules\Careers\Enums;

/**
 * ATS pipeline stages (03-database-schema §4, 06-hr doc §4.2).
 * hired/rejected/withdrawn are terminal — reopening is a data-hygiene
 * exception reserved for admins (logged).
 */
enum ApplicationStatus: string
{
    case New = 'new';
    case Screening = 'screening';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Offer = 'offer';
    case Hired = 'hired';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Screening => 'Screening',
            self::Shortlisted => 'Shortlisted',
            self::Interview => 'Interview',
            self::Offer => 'Offer',
            self::Hired => 'Hired',
            self::Rejected => 'Rejected',
            self::Withdrawn => 'Withdrawn',
        };
    }

    /** Kanban column order. */
    public static function pipeline(): array
    {
        return [
            self::New,
            self::Screening,
            self::Shortlisted,
            self::Interview,
            self::Offer,
            self::Hired,
            self::Rejected,
            self::Withdrawn,
        ];
    }

    /** Statuses that trigger a candidate status email (06-hr §5). */
    public static function emailsOn(): array
    {
        return [
            self::Screening,
            self::Shortlisted,
            self::Interview,
            self::Offer,
            self::Rejected,
        ];
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
