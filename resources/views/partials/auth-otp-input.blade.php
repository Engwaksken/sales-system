{{-- Six-box one-time code for the auth pages. The boxes have no name: the layout script copies the
     digits into the hidden "otp" field, which is what the server validates (digits:6). --}}
<input type="hidden" name="otp" id="otpValue">

<div class="auth-field @error('otp') is-invalid @enderror">
    <label class="auth-label" id="otp-label" for="otp-1">Verification code</label>
    <div class="otp-grid @error('otp') is-invalid @enderror" role="group" aria-labelledby="otp-label" data-otp>
        @for($i = 1; $i <= 6; $i++)
            <input id="otp-{{ $i }}" class="otp-box" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6"
                   autocomplete="{{ $i === 1 ? 'one-time-code' : 'off' }}" aria-label="Digit {{ $i }} of 6"
                   data-otp-box
                   @error('otp') aria-invalid="true" aria-describedby="otp-error" @enderror>
        @endfor
    </div>
    @error('otp')
        <p class="auth-error" id="otp-error"><i class="fas fa-circle-exclamation" aria-hidden="true"></i><span>{{ $message }}</span></p>
    @enderror
    <p class="auth-error" data-otp-client-error role="alert" hidden><i class="fas fa-circle-exclamation" aria-hidden="true"></i><span>Enter all 6 digits of the code.</span></p>
</div>
