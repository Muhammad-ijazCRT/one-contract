<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TwoFactorAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.two_factor.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge', [
            'intent' => $request->session()->get('login.two_factor.intent', 'user'),
        ]);
    }

    public function verify(Request $request, TwoFactorAuth $twoFactor): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10'],
        ]);

        $userId = $request->session()->get('login.two_factor.id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::query()->find($userId);

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget([
                'login.two_factor.id',
                'login.two_factor.remember',
                'login.two_factor.intent',
            ]);

            return redirect()->route('login');
        }

        $secret = $twoFactor->decryptSecret($user->two_factor_secret);

        if (! $secret || ! $twoFactor->verify($secret, $validated['code'])) {
            return back()->withErrors(['code' => 'Invalid authentication code. Please try again.']);
        }

        $remember = (bool) $request->session()->pull('login.two_factor.remember', false);
        $intent = $request->session()->pull('login.two_factor.intent', 'user');
        $request->session()->forget('login.two_factor.id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $route = $intent === 'admin' ? route('admin.dashboard') : route('user.dashboard');

        $message = $intent === 'admin'
            ? 'Admin login successful. Welcome back.'
            : 'Login successful! Welcome back.';

        return redirect()
            ->intended($route)
            ->with('success', $message);
    }
}
