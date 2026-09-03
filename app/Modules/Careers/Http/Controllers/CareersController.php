<?php

namespace App\Modules\Careers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Careers\Enums\Department;
use App\Modules\Careers\Enums\JobStatus;
use App\Modules\Careers\Models\Employee;
use App\Modules\Careers\Models\JobPosting;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public careers surface (06-hr doc §3): /careers listing + the
 * per-job page that NEVER 404s for open/paused/closed postings
 * (closed keeps its URL with a "see similar" state — history/SEO rule),
 * plus /team/{person} public profile pages.
 */
class CareersController extends Controller
{
    public function index(): View
    {
        // Paused roles stay listed (applications on hold, page keeps
        // its URL — hiding it would orphan a live page); closed roles
        // leave the listing and keep their per-job "see similar" URL.
        $grouped = JobPosting::query()
            ->whereIn('status', [JobStatus::Open, JobStatus::Paused])
            ->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>=', now()->toDateString()))
            ->with('city:id,name,slug')
            ->orderBy('department')
            ->orderBy('sort')
            ->get()
            ->groupBy(fn (JobPosting $job): string => $job->department->value);

        return view('leads.careers.index', [
            'grouped' => $grouped,
            'departments' => Department::options(),
        ]);
    }

    public function show(string $slug): View
    {
        $posting = JobPosting::query()
            ->with('city:id,name,slug')
            ->where('slug', mb_strtolower($slug))
            ->first();

        // Drafts are invisible; every other status keeps its URL.
        if (! $posting || $posting->status === JobStatus::Draft) {
            throw new NotFoundHttpException('Job not found.');
        }

        $isOpen = $posting->status === JobStatus::Open
            && ($posting->closes_at === null || $posting->closes_at->gte(now()->startOfDay()));

        $similar = collect();

        if (! $isOpen) {
            $similar = JobPosting::query()
                ->open()
                ->where('department', $posting->department->value)
                ->whereKeyNot($posting->getKey())
                ->orderBy('sort')
                ->take(3)
                ->get();
        }

        return view('leads.careers.job', [
            'posting' => $posting,
            'isOpen' => $isOpen,
            'similar' => $similar,
        ]);
    }

    /** /team/{person} — public profile pages (is_public only). */
    public function person(Employee $employee): View
    {
        if (! $employee->is_public) {
            throw new NotFoundHttpException('Profile not found.');
        }

        return view('leads.careers.person', [
            'employee' => $employee->load(['photo', 'officeCity:id,name,slug']),
        ]);
    }
}
