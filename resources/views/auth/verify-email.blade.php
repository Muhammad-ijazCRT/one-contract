@extends('layouts.auth')

@section('title', 'Verify Email — ONE CONTRACT™')
@section('body_class', 'auth-body--verify')

@section('content')
    @php
        $status = session('status');
        $showStatus = is_string($status)
            && $status !== ''
            && ! str_starts_with($status, 'Registration successful');
        $address = old('email', $email);
    @endphp

    <div class="oc-mail-card">
        <h1>Verify Your Email</h1>
        @if ($address)
            <p>We sent a verification link to <strong>{{ $address }}</strong>. Please check your inbox and spam folder.</p>
        @else
            <p>Enter your email and we will send a verification link.</p>
        @endif

        @if ($showStatus)
            <div class="oc-mail-note" role="status">{{ $status }}</div>
        @endif

        @if ($errors->any())
            <div class="oc-mail-note oc-mail-note--error" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="oc-mail-form">
            @csrf

            @if ($address)
                <input type="hidden" name="email" value="{{ $address }}">
            @else
                <label class="oc-mail-label" for="email">Email address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    placeholder="you@example.com"
                    autocomplete="email"
                >
            @endif

            <button type="submit" class="oc-mail-submit">Resend Verification Email</button>
        </form>

        <a class="oc-mail-back" href="{{ route('login') }}">Back to Login</a>
    </div>
@endsection
