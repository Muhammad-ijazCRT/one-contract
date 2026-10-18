@extends('layouts.auth')

@section('title', 'Register — ONE CONTRACT™')
@section('body_class', 'auth-body--register')
@section('auth_wrapper_class', 'auth-wrapper-wide')

@section('content')
    <div class="oc-register-page">
        <div class="oc-register-top">
            <button type="button" class="oc-register-back" data-register-back aria-label="Go back">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
        </div>

        @include('partials.auth.brand', ['variant' => 'login'])

        <header class="oc-login-head" data-register-head>
            <h1 data-onb-title>Create Your <em>Account</em></h1>
            <p data-onb-subtitle>Create your account to continue.</p>
        </header>

        @include('partials.auth.stepper')

        @if ($errors->any())
            <div class="oc-onb-alert oc-onb-alert--error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="oc-register-form" data-auth-wizard data-wizard-mode="register" novalidate>
            @csrf

            {{-- Step 1: Account --}}
            <div class="auth-wizard-panel is-active" data-wizard-step="1">
                <div class="oc-login-form">
                    <div class="oc-login-field">
                        <label class="oc-login-field-label" for="name">Full Name</label>
                        <div class="oc-login-field-inner">
                            <i class="fa-regular fa-user" aria-hidden="true"></i>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="Enter your full name" autocomplete="name">
                        </div>
                    </div>

                    <div class="oc-login-field">
                        <label class="oc-login-field-label" for="email">Email Address</label>
                        <div class="oc-login-field-inner">
                            <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required placeholder="Enter your email address" autocomplete="email">
                        </div>
                    </div>

                    <div class="oc-login-field oc-login-field--phone">
                        <label class="oc-login-field-label" for="phone">Phone Number</label>
                        <div class="oc-login-field-inner oc-login-field-inner--phone">
                            <i class="fa-solid fa-phone" aria-hidden="true"></i>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="Enter your phone number" autocomplete="tel">
                            <div class="oc-register-phone-dial">
                                @include('partials.phone-country-picker', [
                                    'countries' => $countries,
                                    'default' => 'NG',
                                    'label' => null,
                                ])
                            </div>
                        </div>
                    </div>

                    <div class="oc-login-field oc-login-field--password">
                        <label class="oc-login-field-label" for="password">Password</label>
                        <div class="oc-login-field-inner">
                            <i class="fa-solid fa-lock" aria-hidden="true"></i>
                            <input type="password" id="password" name="password" required placeholder="Create a strong password" autocomplete="new-password">
                            <button type="button" class="oc-login-eye" data-password-toggle aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                        </div>
                    </div>

                    <div class="oc-login-field oc-login-field--password">
                        <label class="oc-login-field-label" for="password_confirmation">Confirm Password</label>
                        <div class="oc-login-field-inner">
                            <i class="fa-solid fa-lock" aria-hidden="true"></i>
                            <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="Confirm your password" autocomplete="new-password">
                            <button type="button" class="oc-login-eye" data-password-toggle aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                        </div>
                    </div>

                    <div class="oc-login-field">
                        <label class="oc-login-field-label" for="referral_code">Referral Code <small>(Optional)</small></label>
                        <div class="oc-login-field-inner">
                            <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                            @php $hasRef = request()->query('ref') || (! empty($referral)); @endphp
                            <input type="text" id="referral_code" name="referral_code" value="{{ old('referral_code', $referral) }}" placeholder="Enter referral code (optional)" @if($hasRef) readonly @endif>
                        </div>
                    </div>

                    <label class="oc-login-check oc-register-terms">
                        <input type="checkbox" name="terms" id="terms" value="1" required {{ old('terms') ? 'checked' : '' }}>
                        <span class="oc-login-check-box" aria-hidden="true"></span>
                        <span>I agree to the <a href="{{ route('legal.show', 'user-agreement') }}" target="_blank" rel="noopener">Terms of Service</a> and <a href="{{ route('legal.show', 'privacy-policy') }}" target="_blank" rel="noopener">Privacy Policy</a>.</span>
                    </label>
                    @error('terms')
                        <p class="oc-onb-terms-error" id="terms-error" role="alert">{{ $message }}</p>
                    @else
                        <p class="oc-onb-terms-error" id="terms-error" role="alert" hidden>You must accept the Terms of Service and Privacy Policy.</p>
                    @enderror

                    <button type="button" class="oc-login-submit" data-wizard-next>
                        Continue <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            {{-- Step 2: Verify hub --}}
            <div class="auth-wizard-panel" data-wizard-step="2" hidden>
                @include('partials.auth.verify-hub')
            </div>

            {{-- Step 3: Identity document --}}
            <div class="auth-wizard-panel" data-wizard-step="3" hidden>
                @include('partials.auth.register-id-document')
            </div>

            {{-- Step 4: Selfie --}}
            <div class="auth-wizard-panel" data-wizard-step="4" hidden>
                <header class="oc-reg-selfie-hero">
                    <div class="oc-reg-doc-hero-icon" aria-hidden="true"><i class="fa-solid fa-user"></i></div>
                    <h2>Take a <em>Selfie</em></h2>
                    <p>Take a clear selfie to verify your identity.</p>
                </header>

                @include('partials.selfie-capture')

                <button type="button" class="oc-login-submit" data-wizard-next>
                    Continue <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
                </button>
            </div>
        </form>

        <div data-wizard-extra-step="1">
            <p class="oc-login-foot">
                Already have an account? <a href="{{ route('login') }}">Log in</a>
            </p>
        </div>
    </div>
@endsection
