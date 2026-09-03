<?php

namespace App\Modules\Portal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Portal\Models\PortalNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class NotificationsController extends Controller
{
    public function index(): View
    {
        $notifications = PortalNotification::query()
            ->forUser((string) auth()->id())
            ->latest()
            ->paginate(20);

        return view('portal.notifications.index', ['notifications' => $notifications]);
    }

    /** Mark one read (04 doc §3). */
    public function read(string $notification): RedirectResponse
    {
        $row = PortalNotification::query()
            ->forUser((string) auth()->id())
            ->findOrFail($notification);

        $row->markRead();

        if ($row->url !== null && $row->url !== '') {
            return redirect($row->url);
        }

        return back()->with('status', 'Notification marked as read.');
    }

    /** Mark-all — the noisy-day escape hatch. */
    public function readAll(): RedirectResponse
    {
        PortalNotification::query()
            ->forUser((string) auth()->id())
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }
}
