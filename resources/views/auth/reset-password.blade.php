@extends('layouts.auth')

@section('title', 'Reset Password')
@section('heading', 'Choose a new password')
@section('lead', 'Pick a strong password you have not used for this account before.')

@section('content')
<form method="POST" action="{{ route('password.update') }}" class="auth-form">
    @csrf

    <div class="auth-field @error('password') is-invalid @enderror">
        <label for="password">New password</label>
        <div class="auth-control">
            <i class="fas fa-lock leading-icon" aria-hidden="true"></i>
            <input id="password" type="password" name="password"
                   autocomplete="new-password" required autofocus
                   aria-describedby="@error('password')password-error @enderror password-strength"
                   @error('password') aria-invalid="true" @enderror>
            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false" aria-controls="password">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </button>
        </div>
        @error('password')
            <p class="auth-error" id="password-error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i><span>{{ $message }}</span></p>
        @enderror
        @include('partials.auth-password-strength', ['for' => 'password'])
    </div>

    <div class="auth-field">
        <label for="password_confirmation">Confirm new password</label>
        <div class="auth-control">
            <i class="fas fa-lock leading-icon" aria-hidden="true"></i>
            <input id="password_confirmation" type="password" name="password_confirmation"
                   autocomplete="new-password" required aria-describedby="password_confirmation-match">
            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false" aria-controls="password_confirmation">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </button>
        </div>
        <p class="auth-match" id="password_confirmation-match" data-password-match="password" data-confirm="password_confirmation" hidden>
            <i class="fas fa-circle-xmark" aria-hidden="true"></i><span></span>
        </p>
    </div>

    <button class="auth-submit" type="submit" data-loading-text="Updating password…">
        <span class="auth-spinner" aria-hidden="true"></span>
        <span data-label>Update password</span>
    </button>
</form>

<p class="auth-alt">
    <a class="auth-link auth-link-back" href="{{ route('login') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Back to sign in</a>
</p>
@endsection
