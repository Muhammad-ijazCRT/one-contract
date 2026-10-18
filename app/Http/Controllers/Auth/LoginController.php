<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Support\CountryList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login', [
            'countries' => CountryList::names(),
        ]);
    }

    public function showAdminLoginForm(): View
    {
        return view('auth.admin-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'country_id' => ['required', 'string', 'max:10'],
        ], [
            'country_id.required' => 'Please select your country ID.',
        ]);

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email', 'remember', 'country_id'))
                ->withErrors(['email' => 'Invalid email or password.']);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->role === UserRole::Admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email', 'remember', 'country_id'))
                ->withErrors(['email' => 'Admin accounts must sign in via the admin portal.']);
        }

        if (! config('app.debug') && ! $user->hasVerifiedEmail()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email', 'remember', 'country_id'))
                ->withErrors(['email' => 'Please verify your email address before logging in. Check your inbox for the verification link.'])
                ->with('unverified_email', $user->email);
        }

        if ($user->status !== UserStatus::Active) {
            Auth::logout();

            return back()
                ->withInput($request->only('email', 'country_id'))
                ->withErrors(['email' => 'Your account has been suspended.']);
        }

        if (! config('app.debug') && ($error = $this->verifyLoginCountry($user, $validated['country_id']))) {
            Auth::logout();

            return back()
                ->withInput($request->only('email', 'remember', 'country_id'))
                ->withErrors(['country_id' => $error]);
        }

        if ($challenge = $this->redirectToTwoFactorChallenge($request, $user, 'user')) {
            return $challenge;
        }

        return redirect()
            ->intended(route('user.dashboard'))
            ->with('success', 'Login successful! Welcome back.');
    }

    public function adminLogin(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Invalid email or password.']);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->role !== UserRole::Admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'These credentials are not authorized for admin access.']);
        }

        if ($user->status !== UserStatus::Active) {
            Auth::logout();

            return back()->withErrors(['email' => 'This admin account has been suspended.']);
        }

        if ($challenge = $this->redirectToTwoFactorChallenge($request, $user, 'admin')) {
            return $challenge;
        }

        return redirect()
            ->intended(route('admin.dashboard'))
            ->with('success', 'Welcome back, Admin.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function verifyLoginCountry($user, string $countryId): ?string
    {
        if (! $user->country_id) {
            return 'Your account has no country ID on file. Please contact support.';
        }

        if (strcasecmp((string) $user->country_id, $countryId) !== 0) {
            return 'Country ID does not match your registered country.';
        }

        return null;
    }

    private function redirectToTwoFactorChallenge(Request $request, $user, string $guardIntent): ?RedirectResponse
    {
        if (! $user->hasTwoFactorEnabled()) {
            return null;
        }

        $remember = $request->boolean('remember');

        Auth::logout();

        $request->session()->put('two_factor', [
            'user_id' => $user->id,
            'remember' => $remember,
            'intended' => $guardIntent === 'admin' ? route('admin.dashboard') : route('user.dashboard'),
            'guard_intent' => $guardIntent,
        ]);

        return redirect()->route('two-factor.challenge');
    }
}
