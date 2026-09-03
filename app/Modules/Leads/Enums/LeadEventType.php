<?php

namespace App\Modules\Leads\Enums;

/**
 * Timeline entry types (03-database-schema §5 lead_events). `system`
 * covers machine-generated entries (SLA breach, escalation, dedupe flag).
 */
enum LeadEventType: string
{
    case Note = 'note';
    case Status = 'status';
    case Email = 'email';
    case Call = 'call';
    case Sms = 'sms';
    case Form = 'form';
    case Assign = 'assign';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Note',
            self::Status => 'Status change',
            self::Email => 'Email',
            self::Call => 'Call',
            self::Sms => 'SMS',
            self::Form => 'Form submission',
            self::Assign => 'Assignment',
            self::System => 'System',
        };
    }
}
