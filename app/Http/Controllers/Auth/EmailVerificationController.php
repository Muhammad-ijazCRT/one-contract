<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View
    {
        return view('auth.verify-email', [
            'email' => $request->session()->get('email') ?? $request->query('email'),
        ]);
    }

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::query()->findOrFail($id);

        if (! URL::hasValidSignature($request)) {
            return redirect()
                ->route('verification.notice')
                ->withErrors(['email' => 'This verification link is invalid or has expired.']);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()
                ->route('login')
                ->with('status', 'Your email is already verified. You can log in now.');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return redirect()
            ->route('login')
            ->with('status', 'Your email has been verified successfully. You can log in now.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', 'This email is already verified. You can log in.');
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'email' => 'Could not send verification email. Please check mail settings or try again shortly.',
            ]);
        }

        return back()->with('status', 'A new verification link has been sent to your email address.');
    }
}
