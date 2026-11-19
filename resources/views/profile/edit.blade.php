@extends('layouts.app')
@section('title', 'My profile')
@section('content')
@php
    $initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
    $field = 'block w-full rounded-lg border-slate-300 text-sm';
    $tabs = ['details' => ['Details', 'fa-user'], 'password' => ['Password', 'fa-lock'], 'security' => ['Sign-in & preferences', 'fa-shield-halved']];
    $startTab = $errors->password->any() ? 'password' : ($errors->details->any() ? 'details' : (session('profile_tab') ?: 'details'));
@endphp
<div class="mx-auto max-w-2xl">
    <h1 class="text-xl font-semibold text-slate-800">My profile</h1>
    <p class="mb-5 text-sm text-slate-500">Your details, photo and password. Changes apply on the website and in the mobile app.</p>

    @if(session('success'))<div data-flash class="mb-4 rounded bg-emerald-50 p-3 text-emerald-800">{{ session('success') }}</div>@endif

    {{-- Summary --}}
    <div class="mb-5 flex items-center gap-4 rounded-lg bg-white p-5 shadow">
        @if($photoUrl)
            <img src="{{ $photoUrl }}" alt="" class="h-16 w-16 shrink-0 rounded-full object-cover">
        @else
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full text-xl font-bold text-white" style="background: var(--theme-primary)" aria-hidden="true">{{ $initials ?: '?' }}</div>
        @endif
        <div class="min-w-0">
            <div class="truncate text-lg font-semibold text-slate-800">{{ $user->name }}</div>
            <div class="truncate text-sm text-slate-500">{{ $user->email }}</div>
            <div class="mt-1 flex flex-wrap gap-2 text-xs">
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-600">{{ $roleLabel }}</span>
                @if($branchName)<span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-600"><i class="fas fa-store mr-1"></i>{{ $branchName }}</span>@endif
                @if($user->created_at)<span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-600">Member since {{ $user->created_at->format('M Y') }}</span>@endif
            </div>
        </div>
    </div>

    <div class="mb-4 flex gap-1 overflow-x-auto border-b border-slate-200" role="tablist" aria-label="Profile sections" data-profile-tabs data-start="{{ $startTab }}">
        @foreach($tabs as $key => [$label, $icon])
            <button type="button" role="tab" id="profile-tab-{{ $key }}" aria-controls="profile-panel-{{ $key }}"
                    data-tabgroup="profile" data-tab="{{ $key }}"
                    aria-selected="{{ $key === $startTab ? 'true' : 'false' }}" tabindex="{{ $key === $startTab ? '0' : '-1' }}"
                    class="-mb-px inline-flex shrink-0 items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2 text-sm font-medium {{ $key === $startTab ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500' }}">
                <i class="fas {{ $icon }}" aria-hidden="true"></i>{{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Details --}}
    <div role="tabpanel" id="profile-panel-details" aria-labelledby="profile-tab-details" data-tabpanel="profile" data-tab="details" class="{{ $startTab === 'details' ? '' : 'hidden' }}">
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="rounded-lg bg-white shadow">
        @csrf
        @method('PUT')
        <div class="border-b px-5 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500">Your details</div>
        <div class="space-y-4 p-5">
            @if($errors->details->any())<div class="rounded bg-red-50 p-3 text-sm text-red-700">{{ $errors->details->first() }}</div>@endif

            <div>
                <span class="mb-1 block text-sm text-slate-600">Photo</span>
                <div class="flex flex-wrap items-center gap-4">
                    <input type="file" name="photo" id="profile-photo" accept="image/png,image/jpeg,image/webp"
                           class="block min-w-0 flex-1 text-sm text-slate-600 file:mr-4 file:rounded file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                    @if($photoUrl)
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remove_photo" value="1" class="rounded border-slate-300"> Remove photo
                        </label>
                    @endif
                </div>
                <p class="mt-1 text-xs text-slate-400">JPG, PNG or WebP, up to 4 MB. A square picture looks best.</p>
            </div>

            <label class="block text-sm">
                <span class="mb-1 block text-slate-600">Full name</span>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="150" autocomplete="name" class="{{ $field }}">
            </label>

            <label class="block text-sm">
                <span class="mb-1 block text-slate-600">Email address</span>
                <input type="email" value="{{ $user->email }}" disabled class="{{ $field }} bg-slate-50 text-slate-500">
                <span class="mt-1 block text-xs text-slate-400">You sign in with this address. Ask an administrator if it needs to change.</span>
            </label>

            @if($isCustomer)
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Phone number</span>
                    <input type="tel" name="phone" value="{{ old('phone', $customer?->phone) }}" required maxlength="50" autocomplete="tel" class="{{ $field }}">
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block text-slate-600">Delivery address <span class="text-slate-400">(optional)</span></span>
                    <textarea name="address" rows="2" maxlength="500" autocomplete="street-address" class="{{ $field }}">{{ old('address', $customer?->address) }}</textarea>
                </label>
            @endif
        </div>
        <div class="flex justify-end border-t px-5 py-4">
            <button class="rounded px-5 py-2 text-sm font-semibold text-white" style="background: var(--theme-primary)">Save details</button>
        </div>
    </form>
    </div>

    {{-- Password --}}
    <div role="tabpanel" id="profile-panel-password" aria-labelledby="profile-tab-password" data-tabpanel="profile" data-tab="password" class="{{ $startTab === 'password' ? '' : 'hidden' }}">
    <form method="POST" action="{{ route('profile.password') }}" class="rounded-lg bg-white shadow">
        @csrf
        @method('PUT')
        <div class="border-b px-5 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500">Change password</div>
        <div class="space-y-4 p-5">
            @if($errors->password->any())<div class="rounded bg-red-50 p-3 text-sm text-red-700">{{ $errors->password->first() }}</div>@endif
            <label class="block text-sm">
                <span class="mb-1 block text-slate-600">Current password</span>
                <input type="password" name="current_password" required autocomplete="current-password" class="{{ $field }}">
            </label>
            <label class="block text-sm">
                <span class="mb-1 block text-slate-600">New password</span>
                <input type="password" name="password" required minlength="8" autocomplete="new-password" class="{{ $field }}">
                <span class="mt-1 block text-xs text-slate-400">At least 8 characters, with upper and lower case letters and a number.</span>
            </label>
            <label class="block text-sm">
                <span class="mb-1 block text-slate-600">Confirm new password</span>
                <input type="password" name="password_confirmation" required autocomplete="new-password" class="{{ $field }}">
            </label>
            <p class="text-xs text-slate-500"><i class="fas fa-circle-info mr-1"></i>Changing your password signs you out of other browsers and the mobile app.</p>
        </div>
        <div class="flex justify-end border-t px-5 py-4">
            <button class="rounded px-5 py-2 text-sm font-semibold text-white" style="background: var(--theme-primary)">Change password</button>
        </div>
    </form>
    </div>

    {{-- Related settings --}}
    <div role="tabpanel" id="profile-panel-security" aria-labelledby="profile-tab-security" data-tabpanel="profile" data-tab="security" class="{{ $startTab === 'security' ? '' : 'hidden' }}">
    <div class="rounded-lg bg-white shadow">
        <div class="border-b px-5 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500">Sign-in and preferences</div>
        <a href="{{ route('passkeys.index') }}" class="flex items-center gap-3 border-b px-5 py-4 hover:bg-slate-50">
            <i class="fas fa-fingerprint w-5 text-center text-slate-500" aria-hidden="true"></i>
            <span class="flex-1">
                <span class="block font-semibold text-slate-800">Passkeys</span>
                <span class="block text-xs text-slate-500">{{ $passkeyCount ? $passkeyCount.' '.\Illuminate\Support\Str::plural('passkey', $passkeyCount).' set up' : 'Sign in with your fingerprint or face' }}</span>
            </span>
            <i class="fas fa-chevron-right text-slate-300" aria-hidden="true"></i>
        </a>
        @unless($isCustomer)
            <a href="{{ route('signature.edit') }}" class="flex items-center gap-3 border-b px-5 py-4 hover:bg-slate-50">
                <i class="fas fa-signature w-5 text-center text-slate-500" aria-hidden="true"></i>
                <span class="flex-1">
                    <span class="block font-semibold text-slate-800">My signature</span>
                    <span class="block text-xs text-slate-500">Printed on your invoices and receipts</span>
                </span>
                <i class="fas fa-chevron-right text-slate-300" aria-hidden="true"></i>
            </a>
        @endunless
        <a href="{{ route('notification-settings.edit') }}" class="flex items-center gap-3 px-5 py-4 hover:bg-slate-50">
            <i class="fas fa-bell w-5 text-center text-slate-500" aria-hidden="true"></i>
            <span class="flex-1">
                <span class="block font-semibold text-slate-800">Notification settings</span>
                <span class="block text-xs text-slate-500">Choose what you are told about</span>
            </span>
            <i class="fas fa-chevron-right text-slate-300" aria-hidden="true"></i>
        </a>
    </div>
    </div>
</div>

@push('scripts')
<script>
// Tabs: remembered in the address (#password, #security) so links and reloads
// land on the same tab; arrow keys move between them.
(function () {
    var bar = document.querySelector('[data-profile-tabs]');
    if (!bar) return;
    var buttons = Array.prototype.slice.call(bar.querySelectorAll('[role="tab"]'));
    var keys = buttons.map(function (b) { return b.dataset.tab; });

    function open(key, focus) {
        switchTab('profile', key);
        if (history.replaceState) history.replaceState(null, '', key === 'details' ? location.pathname + location.search : '#' + key);
        if (focus) buttons[keys.indexOf(key)].focus();
    }

    var fromHash = location.hash.slice(1);
    // Errors and "saved" messages from the server win over the address.
    if (bar.dataset.start === 'details' && keys.indexOf(fromHash) > -1) open(fromHash, false);

    buttons.forEach(function (button, index) {
        button.addEventListener('click', function () { open(button.dataset.tab, false); });
        button.addEventListener('keydown', function (event) {
            var next = { ArrowRight: index + 1, ArrowLeft: index - 1, Home: 0, End: buttons.length - 1 }[event.key];
            if (next === undefined) return;
            event.preventDefault();
            open(keys[(next + buttons.length) % buttons.length], true);
        });
    });
})();

// Picking a new photo makes "Remove photo" meaningless; untick it.
document.getElementById('profile-photo')?.addEventListener('change', function () {
    var remove = document.querySelector('input[name="remove_photo"]');
    if (remove && this.files.length) remove.checked = false;
});
</script>
@endpush
@endsection
