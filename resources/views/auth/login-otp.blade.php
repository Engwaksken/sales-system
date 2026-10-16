@extends('layouts.auth')

@section('title', 'Verify Login')
@section('subtitle', 'Secure sign-in verification')

@section('content')
<div class="auth-intro">
    <h2>Check your email</h2>
    <p>
        Enter the 6-digit code sent to <strong>{{ $maskedEmail }}</strong>.
        For your security, the code expires in {{ $expiresMinutes }} minutes.
    </p>
</div>

<form method="POST" action="{{ route('login.otp.verify') }}" id="loginOtpForm">
    @csrf

    <input type="hidden" name="otp" id="otpValue">

    <div class="otp-grid" aria-label="6 digit verification code">
        @for($i = 0; $i < 6; $i++)
            <div class="auth-control">
                <input
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="1"
                    class="otp-box"
                    autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                    data-otp-box
                >
            </div>
        @endfor
    </div>

    <button class="auth-submit" type="submit">
        <i class="fas fa-shield-check"></i>
        Verify & Continue to Dashboard
    </button>
</form>

<form method="POST" action="{{ route('login.otp.resend') }}" class="resend-row">
    @csrf
    Didn't receive the code?
    <button type="submit" class="link-button">Resend OTP</button>
</form>

<a href="{{ route('login') }}" class="auth-back">
    <i class="fas fa-arrow-left"></i>
    Back to sign in
</a>
@endsection

@push('scripts')
<script>
(function () {
    const boxes = Array.from(document.querySelectorAll('[data-otp-box]'));
    const hidden = document.getElementById('otpValue');
    const form = document.getElementById('loginOtpForm');

    function sync() {
        hidden.value = boxes.map(box => box.value.replace(/\D/g, '')).join('');
    }

    boxes.forEach((box, index) => {
        box.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(-1);
            sync();

            if (this.value && index < boxes.length - 1) {
                boxes[index + 1].focus();
                boxes[index + 1].select();
            }
        });

        box.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' && !this.value && index > 0) {
                boxes[index - 1].focus();
            }
        });

        box.addEventListener('paste', function (event) {
            const value = (event.clipboardData || window.clipboardData)
                .getData('text')
                .replace(/\D/g, '')
                .slice(0, 6);

            if (!value) return;

            event.preventDefault();

            value.split('').forEach((digit, i) => {
                if (boxes[i]) boxes[i].value = digit;
            });

            sync();

            const next = boxes[Math.min(value.length, boxes.length) - 1];
            if (next) next.focus();
        });
    });

    form.addEventListener('submit', function (event) {
        sync();

        if (hidden.value.length !== 6) {
            event.preventDefault();
            boxes.find(box => !box.value)?.focus();
        }
    });

    boxes[0]?.focus();
})();
</script>
@endpush
