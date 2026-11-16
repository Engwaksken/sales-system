@extends('layouts.auth')

@section('title', 'Sign In')
@section('heading', 'Sign in to your account')
@section('lead', 'Welcome back. Enter your details to continue.')

@section('content')
<form method="POST" action="{{ route('login') }}" class="auth-form">
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

    <div class="auth-field @error('password') is-invalid @enderror">
        <label for="password">Password</label>
        <div class="auth-control">
            <i class="fas fa-lock leading-icon" aria-hidden="true"></i>
            <input id="password" type="password" name="password"
                   autocomplete="current-password" required
                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false" aria-controls="password">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </button>
        </div>
        @error('password')
            <p class="auth-error" id="password-error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i><span>{{ $message }}</span></p>
        @enderror
    </div>

    <div class="auth-row">
        <label class="auth-check">
            <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
            <span>Remember me</span>
        </label>
        <a class="auth-link" href="{{ route('password.request') }}">Forgot password?</a>
    </div>

    <button class="auth-submit" type="submit" data-loading-text="Signing in…">
        <span class="auth-spinner" aria-hidden="true"></span>
        <span data-label>Sign in</span>
    </button>
</form>

<p class="auth-alt">
    New customer?
    <a class="auth-link" href="{{ route('customer.signup') }}">Create an account</a>
</p>
@endsection
