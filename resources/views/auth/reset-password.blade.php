@extends('layouts.auth')

@section('title', 'Reset Password — One-Contract™')

@section('content')
    <div class="auth-card">
        <h1>Reset Password</h1>
        <p class="auth-subtitle">Choose a new password for your account</p>

        @if ($errors->any())
            <div class="auth-alert auth-alert-error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="auth-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autofocus placeholder="you@example.com">
            </div>
            @include('partials.password-field', [
                'id' => 'password',
                'name' => 'password',
                'label' => 'New Password',
                'required' => true,
                'autocomplete' => 'new-password',
            ])
            @include('partials.password-field', [
                'id' => 'password_confirmation',
                'name' => 'password_confirmation',
                'label' => 'Confirm New Password',
                'required' => true,
                'autocomplete' => 'new-password',
            ])
            <button type="submit" class="btn btn-gold btn-block">Reset Password</button>
        </form>

        <p class="auth-footer-text">
            Remembered your password? <a href="{{ route('login') }}">Back to Login</a>
        </p>
    </div>
@endsection
