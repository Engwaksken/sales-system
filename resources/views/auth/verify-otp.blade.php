@extends('layouts.auth')

@section('title', 'Verify Code')
@section('heading', 'Check your email')
@section('lead')
    We sent a 6-digit code to <strong>{{ $maskedEmail }}</strong>. It expires in {{ $expiresMinutes }} minutes.
@endsection

@section('content')
<form id="otpForm" method="POST" action="{{ route('password.otp.verify') }}" class="auth-form">
    @csrf

    @include('partials.auth-otp-input')

    <button class="auth-submit" type="submit" data-loading-text="Verifying...">
        <span class="auth-spinner" aria-hidden="true"></span>
        <span data-label>Verify code</span>
    </button>
</form>

<form method="POST" action="{{ route('password.otp.resend') }}" class="auth-alt">
    @csrf
    <span>Didn't get a code?</span>
    <button type="submit" class="link-button" data-loading-text="Sending...">Resend code</button>
</form>

<p class="auth-alt auth-alt-tight">
    <a class="auth-link auth-link-back" href="{{ route('password.request') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Use a different email</a>
</p>
@endsection
