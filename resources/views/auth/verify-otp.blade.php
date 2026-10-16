@extends('layouts.auth')
@section('title','Verify Code')
@section('subtitle','Secure password recovery')
@section('content')
<div class="auth-intro"><h2>Verify your identity</h2><p>Enter the 6-digit code sent to <strong>{{ $maskedEmail }}</strong>. For your security, it expires in {{ $expiresMinutes }} minutes.</p></div>
<form id="otpForm" method="POST" action="{{ route('password.otp.verify') }}">@csrf
<input type="hidden" name="otp" id="otpValue">
<div class="otp-grid" id="otpGrid">@for($i=0;$i<6;$i++)<input class="otp-box" type="text" inputmode="numeric" maxlength="1" pattern="[0-9]" aria-label="OTP digit {{ $i+1 }}">@endfor</div>
<button class="auth-submit" type="submit"><i class="fas fa-shield-check"></i> Verify code</button>
</form>
<form class="resend-row" method="POST" action="{{ route('password.otp.resend') }}">@csrf<span>Didn't receive the code? </span><button class="link-button" type="submit">Resend code</button></form>
<a class="auth-back" href="{{ route('password.request') }}"><i class="fas fa-arrow-left"></i> Use another email</a>
@push('scripts')<script>
const otpBoxes=[...document.querySelectorAll('.otp-box')];const otpHidden=document.getElementById('otpValue');
function syncOtp(){otpHidden.value=otpBoxes.map(x=>x.value).join('')}
otpBoxes.forEach((box,i)=>{box.addEventListener('input',()=>{box.value=box.value.replace(/\D/g,'').slice(0,1);syncOtp();if(box.value&&i<5)otpBoxes[i+1].focus()});box.addEventListener('keydown',e=>{if(e.key==='Backspace'&&!box.value&&i>0)otpBoxes[i-1].focus()});box.addEventListener('paste',e=>{e.preventDefault();const digits=(e.clipboardData.getData('text')||'').replace(/\D/g,'').slice(0,6);digits.split('').forEach((d,j)=>{if(otpBoxes[j])otpBoxes[j].value=d});syncOtp();otpBoxes[Math.min(digits.length,5)]?.focus()})});
document.getElementById('otpForm').addEventListener('submit',syncOtp);otpBoxes[0]?.focus();
</script>@endpush
@endsection
