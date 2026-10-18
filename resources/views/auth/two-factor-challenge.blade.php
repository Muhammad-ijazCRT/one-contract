@extends('layouts.auth')

@section('title', 'Two-Factor Authentication — One-Contract™')

@section('content')
    <div class="auth-card">
        <h1>Two-Factor Authentication</h1>
        <p class="auth-subtitle">Enter the 6-digit code from your authenticator app to continue.</p>

        @if ($errors->any())
            <div class="auth-alert auth-alert-error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('two-factor.verify') }}" class="auth-form">
            @csrf
            <div class="form-group">
                <label for="code">Authentication Code</label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    value="{{ old('code') }}"
                    required
                    autofocus
                    autocomplete="one-time-code"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="6"
                    placeholder="000000"
                    class="two-factor-code-input"
                >
            </div>
            <button type="submit" class="btn btn-gold btn-block">Verify &amp; Login</button>
        </form>

        <p class="auth-footer-text">
            @if ($intent === 'admin')
                <a href="{{ route('admin.login') }}">Back to admin login</a>
            @else
                <a href="{{ route('login') }}">Back to login</a>
            @endif
        </p>
    </div>
@endsection
