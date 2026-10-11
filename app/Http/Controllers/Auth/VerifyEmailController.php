<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Mark the member's email address as verified from the link of the email.
     * No login needed: the signed link (60 minutes) and the hash of the address prove it.
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Already logged in on this browser: straight to the dashboard.
        if ($request->user()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        return redirect()->route('login')
            ->with('status', 'Votre adresse email est vérifiée ! Vous pouvez maintenant vous connecter.');
    }
}
