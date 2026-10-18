@extends('layouts.auth')

@section('title', 'Login — ONE CONTRACT™')
@section('body_class', 'auth-body--login')

@section('content')
    <div class="oc-login-page">
        <div class="oc-login-top">
            <button type="button" class="oc-login-lang" aria-label="Language: English">
                <i class="fa-solid fa-globe" aria-hidden="true"></i>
                <span>English</span>
                <i class="fa-solid fa-chevron-down oc-login-lang-caret" aria-hidden="true"></i>
            </button>
        </div>

        @include('partials.auth.brand', ['variant' => 'login'])

        <div class="oc-login-divider" aria-hidden="true">
            <span></span><i></i><span></span>
        </div>

        <header class="oc-login-head">
            <h1>Welcome <em>Back</em></h1>
            <p>Login to your One Contract account</p>
        </header>

        @if (session('status'))
            <div class="oc-onb-alert oc-onb-alert--success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="oc-onb-alert oc-onb-alert--error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="oc-login-form">
            @csrf

            <div class="oc-login-field">
                <label class="oc-login-field-label" for="email">Email or Username</label>
                <div class="oc-login-field-inner">
                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        placeholder="Enter your email or username"
                        autocomplete="username"
                    >
                </div>
            </div>

            <div class="oc-login-field oc-login-field--country">
                <label class="oc-login-field-label" for="country_id">Country</label>
                <div class="oc-login-field-inner oc-login-field-inner--picker">
                    <i class="fa-solid fa-earth-americas" aria-hidden="true"></i>
                    @include('partials.country-picker', [
                        'countries' => $countries,
                        'placeholder' => 'Select your country',
                        'label' => null,
                    ])
                </div>
            </div>

            <div class="oc-login-field oc-login-field--password">
                <label class="oc-login-field-label" for="password">Password</label>
                <div class="oc-login-field-inner">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        placeholder="Enter your password"
                        autocomplete="current-password"
                    >
                    <button type="button" class="oc-login-eye" data-password-toggle aria-label="Show password">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="oc-login-row">
                <label class="oc-login-check">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span class="oc-login-check-box" aria-hidden="true"></span>
                    <span>Remember me</span>
                </label>
                <a href="{{ route('password.request') }}" class="oc-login-forgot">Forgot Password?</a>
            </div>

            <button type="submit" class="oc-login-submit">
                Login <i class="fa-solid fa-arrow-right-long" aria-hidden="true"></i>
            </button>
        </form>

        @include('partials.auth.social-login', [
            'googleLabel' => 'Google',
        ])

        @if (session('unverified_email'))
            <p class="oc-login-foot">
                <a href="{{ route('verification.notice') }}?email={{ urlencode(session('unverified_email')) }}">Resend verification email</a>
            </p>
        @endif

        <p class="oc-login-foot">
            Don't have an account? <a href="{{ route('register') }}">Create Account</a>
        </p>
    </div>
@endsection
