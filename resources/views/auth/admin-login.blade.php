@extends('layouts.auth')

@section('title', 'Admin Login — One-Contract™')

@section('content')
    <div class="auth-card">
        <h1>Admin Portal</h1>
        @if ($errors->any())
            <div class="auth-alert auth-alert-error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login') }}" class="auth-form">
            @csrf
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@one-contract.com">
            </div>
            @include('partials.password-field', [
                'id' => 'password',
                'name' => 'password',
                'label' => 'Password',
                'required' => true,
                'autocomplete' => 'current-password',
            ])
            <div class="auth-form-row">
                <label class="form-check">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <span>Remember me</span>
                </label>
                <a href="{{ route('password.request') }}" class="auth-inline-link">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-gold btn-block">Admin Login</button>
        </form>

        <p class="auth-footer-text">
            User account? <a href="{{ route('login') }}">Member Login</a>
        </p>
    </div>
@endsection
