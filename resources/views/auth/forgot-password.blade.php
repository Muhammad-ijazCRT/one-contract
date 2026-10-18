@extends('layouts.auth')

@section('title', 'Forgot Password — One-Contract™')

@section('content')
    <div class="auth-card">
        <h1>Forgot Password?</h1>
        <p class="auth-subtitle">Enter your email and we'll send you a link to reset your password</p>

        @if (session('status'))
            <div class="auth-alert auth-alert-success">
                <p>{{ session('status') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="auth-alert auth-alert-error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="auth-form">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com">
            </div>
            <button type="submit" class="btn btn-gold btn-block">Send Reset Link</button>
        </form>

        <p class="auth-footer-text">
            Remembered your password? <a href="{{ route('login') }}">Back to Login</a>
        </p>
    </div>
@endsection
