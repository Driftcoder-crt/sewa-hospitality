<?php

namespace App\Support\Mail;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Mail log row (03-technical-specs/10-email.md §6–7). The idempotency
 * key ("lead.ack:{leadId}") is UNIQUE — MailDispatcher consults it
 * before every send, making queue retries and cron double-fires safe.
 */
class MailLog extends Model
{
    use HasUlids;

    // Contract table (03-technical-specs/10-email.md §6: "mail_log table").
    // The migration created the singular name; the convention guess would
    // be mail_logs and would miss the table entirely.
    protected $table = 'mail_log';

    protected $fillable = ['key', 'template', 'recipient_hash', 'status', 'provider_message_id', 'sent_at'];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }
}
