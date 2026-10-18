<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\GoogleAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    public function loginRedirect(): SymfonyRedirectResponse|RedirectResponse
    {
        if (! GoogleAuth::isConfigured()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Google sign in is not available right now. Please use email and password.']);
        }

        session(['google_oauth_intent' => 'login']);

        return Socialite::driver('google')->redirect();
    }

    public function registerRedirect(Request $request): SymfonyRedirectResponse|RedirectResponse
    {
        return redirect()
            ->route('register', $request->only('ref'))
            ->withErrors([
                'email' => 'Google sign-up is temporarily disabled. Please use the full registration form to complete KYC (ID + selfie).',
            ]);
    }

    public function connectRedirect(): SymfonyRedirectResponse|RedirectResponse
    {
        if (! GoogleAuth::isConfigured()) {
            return redirect()
                ->route($this->profileEditRoute(), ['tab' => 'security'])
                ->withErrors(['google' => 'Google authentication is not available right now. Please try again later.']);
        }

        session(['google_oauth_intent' => 'connect']);

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        if (! GoogleAuth::isConfigured()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Google sign in is not available right now.']);
        }

        $intent = session()->pull('google_oauth_intent', 'login');

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return $this->redirectWithError($intent, 'Google authentication was cancelled or failed. Please try again.');
        }

        return match ($intent) {
            'connect' => $this->handleConnect($googleUser),
            'register' => $this->handleRegister($googleUser),
            default => $this->handleLogin($googleUser),
        };
    }

    public function disconnect(): RedirectResponse
    {
        $user = auth()->user();

        if (! $user->hasGoogleLinked()) {
            return redirect()
                ->route($this->profileEditRoute(), ['tab' => 'security'])
                ->withErrors(['google' => 'No Google account is linked to this profile.']);
        }

        $user->update([
            'google_id' => null,
            'auth_method' => 'standard',
        ]);

        return redirect()
            ->route($this->profileEditRoute(), ['tab' => 'security'])
            ->with('success', 'Google account disconnected. You can sign in with your email and password.');
    }

    private function handleConnect($googleUser): RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Please log in before connecting a Google account.']);
        }

        $user = auth()->user();

        if (strcasecmp($googleUser->getEmail(), $user->email) !== 0) {
            return redirect()
                ->route($this->profileEditRoute(), ['tab' => 'security'])
                ->withErrors(['google' => 'The Google account email must match your One-Contract account email ('.$user->email.').']);
        }

        $existing = User::query()
            ->where('google_id', $googleUser->getId())
            ->where('id', '!=', $user->id)
            ->first();

        if ($existing) {
            return redirect()
                ->route($this->profileEditRoute(), ['tab' => 'security'])
                ->withErrors(['google' => 'This Google account is already linked to another user.']);
        }

        $user->update([
            'google_id' => $googleUser->getId(),
            'auth_method' => 'google',
        ]);

        return redirect()
            ->route($this->profileEditRoute(), ['tab' => 'security'])
            ->with('success', 'Google account connected successfully. You can now sign in with Google.');
    }

    private function handleRegister($googleUser): RedirectResponse
    {
        $email = $googleUser->getEmail();

        if (! $email) {
            return redirect()
                ->route('register')
                ->withErrors(['email' => 'Google did not return an email address. Please register with email and password.']);
        }

        $user = User::query()->where('google_id', $googleUser->getId())->first()
            ?? User::query()->where('email', $email)->first();

        if ($user) {
            if (! $user->google_id) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'auth_method' => 'google',
                ]);
            }

            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            return $this->authenticateUser($user->fresh(), $googleUser);
        }

        return redirect()
            ->route('register')
            ->withInput(['email' => $email, 'name' => $googleUser->getName() ?: Str::before($email, '@')])
            ->withErrors([
                'email' => 'Please complete registration with ID card and selfie verification to create your account.',
            ]);
    }

    private function handleLogin($googleUser): RedirectResponse
    {
        $user = User::query()->where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::query()->where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'auth_method' => 'google',
                ]);

                if (! $user->hasVerifiedEmail()) {
                    $user->markEmailAsVerified();
                }

                $user->refresh();
            }
        }

        if (! $user) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'No account found for this Google email. Please register first or sign up with Google on the registration page.']);
        }

        return $this->authenticateUser($user, $googleUser);
    }

    private function authenticateUser(User $user, $googleUser): RedirectResponse
    {
        if ($user->role === UserRole::Admin) {
            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => 'Admin accounts must sign in via the admin portal.']);
        }

        if ($user->status !== UserStatus::Active) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Your account has been suspended.']);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $user->refresh();
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()
            ->intended(route('user.dashboard'))
            ->with('success', 'Login successful! Welcome back.');
    }

    private function redirectWithError(string $intent, string $message): RedirectResponse
    {
        if ($intent === 'connect' && auth()->check()) {
            return redirect()
                ->route($this->profileEditRoute(), ['tab' => 'security'])
                ->withErrors(['google' => $message]);
        }

        if ($intent === 'register') {
            return redirect()
                ->route('register')
                ->withErrors(['email' => $message]);
        }

        return redirect()
            ->route('login')
            ->withErrors(['email' => $message]);
    }

    private function profileEditRoute(): string
    {
        return auth()->user()?->isAdmin() ? 'admin.profile.edit' : 'user.profile.edit';
    }
}
