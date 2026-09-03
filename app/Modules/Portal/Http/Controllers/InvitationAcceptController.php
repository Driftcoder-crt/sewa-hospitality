<?php

namespace App\Modules\Portal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Portal\Services\InvitationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Magic first-login (04 doc §4.5 + 10-email portal.invite): signed URL
 * → set password → active account → straight into the portal.
 */
class InvitationAcceptController extends Controller
{
    public function __construct(private readonly InvitationService $invitations) {}

    public function __invoke(Request $request, string $token): View|RedirectResponse
    {
        $user = $this->invitations->resolveToken($token);

        if ($user === null) {
            return view('portal.invitations.expired');
        }

        return view('portal.invitations.accept', [
            'token' => $token,
            'user' => $user,
            'organizations' => $user->organizations()->get(),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user = $this->invitations->resolveToken($token);

        if ($user === null) {
            return redirect()->route('login')->withErrors([
                'email' => 'This invitation link is no longer valid — please request a new invite.',
            ]);
        }

        $this->invitations->accept($user, $validated['password']);

        Auth::login($user);

        return redirect()->route('portal.dashboard')
            ->with('status', 'Welcome to your portal! Start with the dashboard — your move and checklist are front and centre.');
    }
}
