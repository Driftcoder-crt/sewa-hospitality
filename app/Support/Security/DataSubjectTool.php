<?php

namespace App\Support\Security;

use App\Models\User;
use App\Modules\Careers\Models\JobApplication;
use App\Modules\Leads\Models\Lead;
use App\Modules\Leads\Models\NewsletterSubscriber;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Portal\Models\PortalDocument;
use App\Modules\Portal\Models\PortalMessage;
use App\Modules\Portal\Models\PortalMove;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Data-subject tool (05-security-reliability.md §1.4, DPDP Act 2023
 * right to access + erasure): an ADMIN surface that exports everything
 * the platform holds for one email address (JSON, human-readable,
 * audited) and can anonymize live leads/applications for that subject.
 *
 * Erasure integrity contract: invoices, payments, quotes and the audit
 * trail are NEVER touched (legal/financial retention wins); leads and
 * applications are anonymized IN PLACE (pipeline history survives,
 * identity does not) — the same field map the retention sweeper uses.
 */
final class DataSubjectTool
{
    /**
     * Everything we hold for this email (access right). Only tables
     * that actually exist in the platform are queried; each section
     * reports its own row count.
     *
     * @return array<string, mixed>
     */
    public static function export(string $email): array
    {
        $email = mb_strtolower(trim($email));

        $users = User::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->get(['id', 'name', 'email', 'phone', 'locale', 'timezone', 'status', 'last_login_at', 'created_at'])
            ->map(fn (User $user): array => array_merge($user->toArray(), ['two_factor_enabled' => (bool) $user->two_factor_enabled]))
            ->all();

        $userId = $users === [] ? null : $users[0]['id'];

        return [
            'subject' => $email,
            'generated_at' => now()->toIso8601String(),
            'users' => $users,
            'leads' => Lead::query()
                ->whereRaw('lower(email) = ?', [$email])
                ->get(['id', 'name', 'email', 'phone', 'company', 'message', 'locale', 'type', 'status', 'created_at'])
                ->all(),
            'job_applications' => JobApplication::query()
                ->whereRaw('lower(applicant_email) = ?', [$email])
                ->get(['id', 'applicant_name', 'applicant_email', 'applicant_phone', 'cover_message', 'status', 'created_at'])
                ->all(),
            'newsletter_subscriptions' => NewsletterSubscriber::query()
                ->whereRaw('lower(email) = ?', [$email])
                ->get(['id', 'email', 'status', 'locale', 'created_at'])
                ->all(),
            'portal_moves' => $userId === null ? [] : PortalMove::query()
                ->where('employee_user_id', $userId)
                ->get(['id', 'reference', 'title', 'stage', 'status', 'created_at'])
                ->all(),
            'portal_messages' => $userId === null ? [] : PortalMessage::query()
                ->where('sender_user_id', $userId)
                ->get(['id', 'portal_thread_id', 'body', 'sender_role', 'created_at'])
                ->all(),
            'portal_documents' => $userId === null ? [] : PortalDocument::query()
                ->where('uploaded_by', $userId)
                ->get(['id', 'title', 'category', 'created_at'])
                ->all(),
            'organizations' => $userId === null ? [] : Organization::query()
                ->whereHas('users', fn ($q) => $q->where('users.id', $userId))
                ->get(['id', 'name', 'created_at'])
                ->all(),
            'audit_log' => $userId === null ? [] : DB::table('activity_log')
                ->where('user_id', $userId)
                ->get(['id', 'context', 'action', 'subject_type', 'subject_id', 'created_at'])
                ->all(),
        ];
    }

    /**
     * Targeted erasure for one email: live leads + job applications are
     * anonymized in place (identity purged, history kept). Returns the
     * affected counts so the action is auditable.
     *
     * @return array{leads: int, applications: int}
     */
    public static function anonymize(string $email): array
    {
        $email = mb_strtolower(trim($email));

        $leads = Lead::query()->whereRaw('lower(email) = ?', [$email])->get();
        $applications = JobApplication::query()->whereRaw('lower(applicant_email) = ?', [$email])->get();

        foreach ($leads as $lead) {
            $lead->forceFill([
                'name' => '[erased]',
                'email' => 'erased+'.$lead->getKey().'@subject.invalid',
                'phone' => null,
                'company' => null,
                'message' => null,
                'enrichment' => null,
                'utm' => null,
                'ip_hash' => null,
                'user_agent' => null,
            ])->save();
        }

        foreach ($applications as $application) {
            if ($application->resume_path) {
                Storage::disk(JobApplication::RESUME_DISK)->delete($application->resume_path);
            }

            $application->forceFill([
                'applicant_name' => '[erased]',
                'applicant_email' => 'erased+'.$application->getKey().'@subject.invalid',
                'applicant_phone' => null,
                'cover_message' => null,
                'resume_path' => null,
            ])->save();
        }

        return ['leads' => $leads->count(), 'applications' => $applications->count()];
    }
}
