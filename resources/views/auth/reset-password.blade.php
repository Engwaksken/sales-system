@extends('layouts.auth')
@section('title','Reset Password')
@section('subtitle','Secure account recovery')
@section('content')
<div class="auth-intro"><h2>Set a new password</h2><p>Choose a strong password you haven’t used for this account before.</p></div>
<form method="POST" action="{{ route('password.update') }}">@csrf
<div class="auth-field"><label for="password">New password</label><div class="auth-control password-control"><i class="fas fa-lock leading-icon"></i><input id="password" type="password" name="password" autocomplete="new-password" required><button type="button" class="password-toggle" onclick="togglePassword(this)" aria-label="Show password"><i class="fas fa-eye"></i></button></div><div class="password-hint">Minimum 8 characters with upper/lowercase letters and at least one number.</div></div>
<div class="auth-field"><label for="password_confirmation">Confirm new password</label><div class="auth-control password-control"><i class="fas fa-lock leading-icon"></i><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required><button type="button" class="password-toggle" onclick="togglePassword(this)" aria-label="Show password"><i class="fas fa-eye"></i></button></div></div>
<button class="auth-submit" type="submit"><i class="fas fa-key"></i> Update password</button>
</form>
@endsection
