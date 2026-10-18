<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Support\TwoFactorAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SecurityController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route($this->profileEditRoute(), ['tab' => 'security']);
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        auth()->user()->update([
            'password' => $request->validated('password'),
        ]);

        return redirect()
            ->route($this->profileEditRoute(), ['tab' => 'security'])
            ->with('success', 'Password updated successfully.');
    }

    public function setupTwoFactor(TwoFactorAuth $twoFactor): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        if ($user->hasTwoFactorConnected()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Two-factor authentication is already connected.',
                ], 422);
            }

            return redirect()
                ->route($this->profileEditRoute(), ['tab' => 'security'])
                ->withErrors(['two_factor' => 'Two-factor authentication is already connected.']);
        }

        $secret = $twoFactor->generateSecret();

        session([
            'two_factor.setup_secret' => $secret,
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'qr_svg' => $twoFactor->qrCodeSvg($user->email, $secret),
                'secret' => $secret,
            ]);
        }

        return redirect()
            ->route($this->profileEditRoute(), ['tab' => 'security'])
            ->with('success', 'Scan the QR code with your authenticator app, then enter the code to connect.');
    }

    public function confirmTwoFactor(Request $request, TwoFactorAuth $twoFactor): RedirectResponse
    {
        $user = auth()->user();
        $secret = session('two_factor.setup_secret');

        if ($user->hasTwoFactorConnected()) {
            return redirect()
                ->route($this->profileEditRoute(), ['tab' => 'security'])
                ->withErrors(['two_factor' => 'Two-factor authentication is already connected.']);
        }

        if (! filled($secret)) {
            return redirect()
                ->route($this->profileEditRoute(), ['tab' => 'security'])
                ->withErrors(['two_factor' => 'Please generate a QR code before connecting two-factor authentication.']);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:10'],
        ]);

        if (! $twoFactor->verify($secret, $validated['code'])) {
            throw ValidationException::withMessages([
                'two_factor_code' => 'Invalid authentication code. Please try again.',
            ]);
        }

        $user->update([
            'two_factor_secret' => $twoFactor->encryptSecret($secret),
            'two_factor_enabled' => true,
        ]);

        session()->forget('two_factor.setup_secret');

        return redirect()
            ->route($this->profileEditRoute(), ['tab' => 'security'])
            ->with('success', 'Two-factor authentication connected successfully.');
    }

    public function toggleTwoFactor(Request $request, TwoFactorAuth $twoFactor): RedirectResponse
    {
        $user = auth()->user();

        if (! $user->hasTwoFactorConnected()) {
            return redirect()
                ->route($this->profileEditRoute(), ['tab' => 'security'])
                ->withErrors(['two_factor' => 'Connect two-factor authentication before enabling it.']);
        }

        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'code' => ['required', 'string', 'max:10'],
        ]);

        $secret = $twoFactor->decryptSecret($user->two_factor_secret);

        if (! $secret || ! $twoFactor->verify($secret, $validated['code'])) {
            throw ValidationException::withMessages([
                'two_factor_code' => 'Invalid authentication code. Please try again.',
            ]);
        }

        $user->update([
            'two_factor_enabled' => $validated['enabled'],
        ]);

        $message = $validated['enabled']
            ? 'Two-factor authentication enabled for login.'
            : 'Two-factor authentication disabled for login.';

        return redirect()
            ->route($this->profileEditRoute(), ['tab' => 'security'])
            ->with('success', $message);
    }

    public function disconnectTwoFactor(Request $request, TwoFactorAuth $twoFactor): RedirectResponse
    {
        $user = auth()->user();

        if (! $user->hasTwoFactorConnected()) {
            return redirect()
                ->route($this->profileEditRoute(), ['tab' => 'security'])
                ->withErrors(['two_factor' => 'Two-factor authentication is not connected.']);
        }

        $validated = $request->validate([
            'password' => ['required', 'string'],
            'code' => ['required', 'string', 'max:10'],
        ]);

        if (! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'two_factor_password' => 'The password is incorrect.',
            ]);
        }

        $secret = $twoFactor->decryptSecret($user->two_factor_secret);

        if (! $secret || ! $twoFactor->verify($secret, $validated['code'])) {
            throw ValidationException::withMessages([
                'two_factor_code' => 'Invalid authentication code. Please try again.',
            ]);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
        ]);

        session()->forget('two_factor.setup_secret');

        return redirect()
            ->route($this->profileEditRoute(), ['tab' => 'security'])
            ->with('success', 'Two-factor authentication disconnected.');
    }

    private function profileEditRoute(): string
    {
        return auth()->user()->isAdmin() ? 'admin.profile.edit' : 'user.profile.edit';
    }
}
