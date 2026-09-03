<?php

namespace App\Modules\Careers\Models;

use App\Modules\Careers\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A job application in the ATS-lite pipeline (06-hr doc §4.2). Resume
 * bytes live on the private local disk (resume_path); previews go
 * through signed URLs only (resumeUrl). Terminal statuses are
 * hard-coded here so no caller has to re-derive them.
 */
class JobApplication extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'job_posting_id', 'applicant_name', 'applicant_email', 'applicant_phone',
        'resume_path', 'resume_media_id', 'cover_message', 'source',
        'source_detail', 'status', 'rating', 'notes', 'idempotency_key',
        'consent_at', 'consent_version',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'consent_at' => 'datetime',
            'rating' => 'integer',
            'notes' => 'array',
        ];
    }

    /* ── Relations ─────────────────────────────────────────────────── */

    public function posting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_posting_id');
    }

    /* ── Pipeline rules ────────────────────────────────────────────── */

    public function isTerminal(): bool
    {
        return in_array($this->status, [ApplicationStatus::Hired, ApplicationStatus::Rejected, ApplicationStatus::Withdrawn], true);
    }

    /** Same candidate, other postings — duplicate detection (06-hr §4.2). */
    public function scopeFromEmail(Builder $query, string $email): Builder
    {
        return $query->where('applicant_email', mb_strtolower(trim($email)));
    }

    /** Signed resume preview URL (15 min) — PII never public. */
    public function resumeUrl(): ?string
    {
        if (! $this->resume_path) {
            return null;
        }

        return url()->temporarySignedRoute('admin.applications.resume', now()->addMinutes(15), [
            'application' => $this->getKey(),
        ]);
    }

    /** Private-disk resume storage namespace. */
    public const string RESUME_DISK = 'local';

    public const string RESUME_DIR = 'resumes';
}
