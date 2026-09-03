<?php

namespace App\Modules\Portal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /** Details, password, locale, notification prefs (04 doc §3). */
    public function edit(): View
    {
        return view('portal.profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9 \-]{6,20}$/'],
            'locale' => ['required', Rule::in(['en', 'hi'])],
            'timezone' => ['required', 'timezone:all'],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
        ]);

        $user->fill([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'locale' => $validated['locale'],
            'timezone' => $validated['timezone'],
        ]);

        if (($validated['password'] ?? null) !== null) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()->route('portal.profile')->with('status', 'Profile updated.');
    }
}
