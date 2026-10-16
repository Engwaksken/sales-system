@extends('layouts.auth')
@section('title','Sign In')
@section('subtitle','Your trusted sales and business management workspace')
@section('content')
<div class="auth-intro"><h2>Welcome back</h2><p>Sign in securely to continue to your account.</p></div>
<form method="POST" action="{{ route('login') }}">
@csrf
<div class="auth-field"><label for="email">Email address</label><div class="auth-control"><i class="fas fa-envelope leading-icon"></i><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></div></div>
<div class="auth-field"><label for="password">Password</label><div class="auth-control password-control"><i class="fas fa-lock leading-icon"></i><input id="password" type="password" name="password" autocomplete="current-password" required><button type="button" class="password-toggle" onclick="togglePassword(this)" aria-label="Show password"><i class="fas fa-eye"></i></button></div></div>
<div class="auth-row"><label class="auth-check"><input type="checkbox" name="remember" value="1"> Remember me</label><a class="auth-link" href="{{ route('password.request') }}">Forgot password?</a></div>
<button class="auth-submit" type="submit"><i class="fas fa-right-to-bracket"></i> Sign in</button>

        <div class="auth-register-link">
            <span>New customer?</span>
            <a href="{{ route('customer.signup') }}">
                <i class="fas fa-user-plus"></i>
                Create Customer Account
            </a>
        </div>

</form>




@endsection
