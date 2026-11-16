{{-- Live password strength meter + checklist for the auth pages. Mirrors the server rule:
     Password::min(8)->letters()->mixedCase()->numbers(). Usage: @include('partials.auth-password-strength', ['for' => 'input-id']) --}}
<div class="auth-strength" id="{{ $for }}-strength" data-password-strength="{{ $for }}">
    <div class="auth-meter-row">
        <div class="auth-meter" data-score="0" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
        <span class="auth-meter-label" data-strength-label aria-live="polite"></span>
    </div>
    <ul class="auth-rules">
        <li data-rule="length"><i class="fas fa-circle-check" aria-hidden="true"></i>At least 8 characters<span class="visually-hidden"></span></li>
        <li data-rule="upper"><i class="fas fa-circle-check" aria-hidden="true"></i>An uppercase letter<span class="visually-hidden"></span></li>
        <li data-rule="lower"><i class="fas fa-circle-check" aria-hidden="true"></i>A lowercase letter<span class="visually-hidden"></span></li>
        <li data-rule="number"><i class="fas fa-circle-check" aria-hidden="true"></i>A number<span class="visually-hidden"></span></li>
    </ul>
</div>
