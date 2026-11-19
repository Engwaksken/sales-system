@extends('layouts.auth')

@section('title', 'Verify Sign In')
@section('heading', 'Check your email')
@section('lead')
    To finish signing in, enter the 6-digit code we sent to <strong>{{ $maskedEmail }}</strong>. It expires in {{ $expiresMinutes }} minutes.
@endsection

@section('content')
<form method="POST" action="{{ route('login.otp.verify') }}" id="loginOtpForm" class="auth-form">
    @csrf

    @include('partials.auth-otp-input')

    <button class="auth-submit" type="submit" data-loading-text="Verifying...">
        <span class="auth-spinner" aria-hidden="true"></span>
        <span data-label>Verify and continue</span>
    </button>
</form>

<form method="POST" action="{{ route('login.otp.resend') }}" class="auth-alt">
    @csrf
    <span>Didn't get a code?</span>
    <button type="submit" class="link-button" data-loading-text="Sending...">Resend code</button>
</form>

<p class="auth-alt auth-alt-tight">
    <a class="auth-link auth-link-back" href="{{ route('login') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to sign in</a>
</p>
@endsection
