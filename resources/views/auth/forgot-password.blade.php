@extends('layouts.auth')
@section('title','Forgot Password')
@section('subtitle','Secure account recovery')
@section('content')
<div class="auth-intro"><h2>Forgot your password?</h2><p>Enter the email address linked to your account. We’ll send you a secure 6-digit reset code.</p></div>
<form method="POST" action="{{ route('password.email') }}">@csrf
<div class="auth-field"><label for="email">Email address</label><div class="auth-control"><i class="fas fa-envelope leading-icon"></i><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></div></div>
<button class="auth-submit" type="submit"><i class="fas fa-paper-plane"></i> Send verification code</button>
</form>
<a class="auth-back" href="{{ route('login') }}"><i class="fas fa-arrow-left"></i> Back to sign in</a>
@endsection
