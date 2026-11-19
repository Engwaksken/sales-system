@extends('layouts.auth')

@section('title', 'Forgot Password')
@section('heading', 'Reset your password')
@section('lead', 'Enter the email address on your account and we will send you a 6-digit code to reset your password.')

@section('content')
<form method="POST" action="{{ route('password.email') }}" class="auth-form">
    @csrf

    <div class="auth-field @error('email') is-invalid @enderror">
        <label for="email">Email address</label>
        <div class="auth-control">
            <i class="fas fa-envelope leading-icon" aria-hidden="true"></i>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   autocomplete="email" inputmode="email" autocapitalize="off" spellcheck="false"
                   placeholder="you@example.com" required autofocus
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        </div>
        @error('email')
            <p class="auth-error" id="email-error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i><span>{{ $message }}</span></p>
        @enderror
    </div>

    <button class="auth-submit" type="submit" data-loading-text="Sending code...">
        <span class="auth-spinner" aria-hidden="true"></span>
        <span data-label>Send reset code</span>
    </button>
</form>

<p class="auth-alt">
    <a class="auth-link auth-link-back" href="{{ route('login') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to sign in</a>
</p>
@endsection
