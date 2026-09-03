<?php

namespace App\Modules\Leads\Models;

use App\Modules\Leads\Enums\NewsletterStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A newsletter subscriber (double opt-in — 03-leads-crm §3/§4.5). One
 * token serves the confirm link AND the one-click unsubscribe link.
 */
class NewsletterSubscriber extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = ['email', 'status', 'token', 'locale', 'confirmed_at', 'unsubscribed_at', 'source'];

    protected function casts(): array
    {
        return [
            'status' => NewsletterStatus::class,
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $subscriber): void {
            $subscriber->token ??= Str::random(48);
        });
    }

    /** Confirm URL (double opt-in). */
    public function confirmUrl(): string
    {
        return route('newsletter.confirm', ['token' => $this->token]);
    }

    /** One-click unsubscribe (CAN-SPAM/DPDP hygiene, 10-email §1.4). */
    public function unsubscribeUrl(): string
    {
        return route('newsletter.unsubscribe', ['token' => $this->token]);
    }
}
