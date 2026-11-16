@extends('layouts.auth')

@section('title', 'Create Account')
@section('heading', 'Create your account')
@section('lead', 'It only takes a minute. We will email you a code to confirm it is you.')

@section('content')
<form method="POST" action="{{ route('customer.register') }}" class="auth-form">
    @csrf

    <div class="auth-field @error('name') is-invalid @enderror">
        <label for="customer_name">Full name</label>
        <div class="auth-control">
            <i class="fas fa-user leading-icon" aria-hidden="true"></i>
            <input id="customer_name" type="text" name="name" value="{{ old('name') }}"
                   autocomplete="name" autocapitalize="words" required autofocus
                   @error('name') aria-invalid="true" aria-describedby="customer_name-error" @enderror>
        </div>
        @error('name')
            <p class="auth-error" id="customer_name-error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i><span>{{ $message }}</span></p>
        @enderror
    </div>

    <div class="auth-field @error('email') is-invalid @enderror">
        <label for="customer_email">Email address</label>
        <div class="auth-control">
            <i class="fas fa-envelope leading-icon" aria-hidden="true"></i>
            <input id="customer_email" type="email" name="email" value="{{ old('email') }}"
                   autocomplete="email" inputmode="email" autocapitalize="off" spellcheck="false"
                   placeholder="you@example.com" required
                   @error('email') aria-invalid="true" aria-describedby="customer_email-error" @enderror>
        </div>
        @error('email')
            <p class="auth-error" id="customer_email-error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i><span>{{ $message }}</span></p>
        @enderror
    </div>

    <div class="auth-field @error('phone') is-invalid @enderror">
        <label for="customer_phone">Phone number</label>
        <div class="auth-control">
            <i class="fas fa-phone leading-icon" aria-hidden="true"></i>
            <input id="customer_phone" type="tel" name="phone" value="{{ old('phone') }}"
                   autocomplete="tel" inputmode="tel" required
                   @error('phone') aria-invalid="true" aria-describedby="customer_phone-error" @enderror>
        </div>
        @error('phone')
            <p class="auth-error" id="customer_phone-error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i><span>{{ $message }}</span></p>
        @enderror
    </div>

    <div class="auth-field @error('address') is-invalid @enderror">
        <label for="customer_address">Address <span class="auth-optional">(optional)</span></label>
        <div class="auth-control auth-control-textarea">
            <i class="fas fa-location-dot leading-icon" aria-hidden="true"></i>
            <textarea id="customer_address" name="address" rows="2" autocomplete="street-address"
                      @error('address') aria-invalid="true" aria-describedby="customer_address-error" @enderror>{{ old('address') }}</textarea>
        </div>
        @error('address')
            <p class="auth-error" id="customer_address-error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i><span>{{ $message }}</span></p>
        @enderror
    </div>

    <div class="auth-field @error('password') is-invalid @enderror">
        <label for="customer_password">Password</label>
        <div class="auth-control">
            <i class="fas fa-lock leading-icon" aria-hidden="true"></i>
            <input id="customer_password" type="password" name="password"
                   autocomplete="new-password" required
                   aria-describedby="@error('password')customer_password-error @enderror customer_password-strength"
                   @error('password') aria-invalid="true" @enderror>
            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false" aria-controls="customer_password">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </button>
        </div>
        @error('password')
            <p class="auth-error" id="customer_password-error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i><span>{{ $message }}</span></p>
        @enderror
        @include('partials.auth-password-strength', ['for' => 'customer_password'])
    </div>

    <div class="auth-field">
        <label for="customer_password_confirmation">Confirm password</label>
        <div class="auth-control">
            <i class="fas fa-lock leading-icon" aria-hidden="true"></i>
            <input id="customer_password_confirmation" type="password" name="password_confirmation"
                   autocomplete="new-password" required aria-describedby="customer_password_confirmation-match">
            <button type="button" class="password-toggle" data-password-toggle aria-label="Show password" aria-pressed="false" aria-controls="customer_password_confirmation">
                <i class="fas fa-eye" aria-hidden="true"></i>
            </button>
        </div>
        <p class="auth-match" id="customer_password_confirmation-match" data-password-match="customer_password" data-confirm="customer_password_confirmation" hidden>
            <i class="fas fa-circle-xmark" aria-hidden="true"></i><span></span>
        </p>
    </div>

    <div class="auth-row">
        <label class="auth-check">
            <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
            <span>Keep me signed in after verification</span>
        </label>
    </div>

    <button class="auth-submit" type="submit" data-loading-text="Creating account…">
        <span class="auth-spinner" aria-hidden="true"></span>
        <span data-label>Create account</span>
    </button>
</form>

<p class="auth-alt">
    Already have an account?
    <a class="auth-link" href="{{ route('login') }}">Sign in</a>
</p>
@endsection
