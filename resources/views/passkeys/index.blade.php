@extends('layouts.app')
@section('title', 'Passkeys')
@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('profile.edit') }}#security" class="mb-2 inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800"><i class="fas fa-arrow-left" aria-hidden="true"></i> My profile</a>
    <h1 class="text-xl font-semibold text-slate-800">Passkeys</h1>
    <p class="mb-5 text-sm text-slate-500">Sign in with your fingerprint, face or device PIN instead of a password and emailed code. The passkey stays on your device; this site only keeps a public key that cannot be used to sign in anywhere else.</p>

    @if(session('success'))<div data-flash class="mb-4 rounded bg-emerald-50 p-3 text-emerald-800">{{ session('success') }}</div>@endif
    <div id="passkey-message" class="mb-4 hidden rounded p-3" role="status"></div>

    <div class="mb-5 rounded-lg bg-white p-5 shadow" id="passkey-add">
        <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Add a passkey on this device</div>
        <div id="passkey-unsupported" class="hidden text-sm text-slate-500">
            This browser can't make passkeys. Try a current version of Chrome, Edge, Safari or Firefox on a device with a fingerprint reader, face unlock or screen lock.
        </div>
        <form id="passkey-form" class="flex flex-wrap items-end gap-3">
            <label class="min-w-0 flex-1 text-sm">
                <span class="mb-1 block text-slate-600">Name <span class="text-slate-400">(optional)</span></span>
                <input type="text" id="passkey-alias" maxlength="60" placeholder="For example: My phone"
                       class="block w-full rounded-lg border-slate-300 text-sm">
            </label>
            <button type="submit" id="passkey-button" class="inline-flex items-center gap-2 rounded px-5 py-2 text-sm font-semibold text-white disabled:opacity-70" style="background: var(--theme-primary)">
                <i class="fas fa-fingerprint" aria-hidden="true"></i><span>Add passkey</span>
            </button>
        </form>
        <p class="mt-3 text-xs text-slate-400">Your device will ask for your fingerprint, face or PIN to confirm it is you.</p>
    </div>

    <div class="rounded-lg bg-white shadow">
        <div class="border-b px-5 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500">Your passkeys</div>
        @forelse($passkeys as $passkey)
            <div class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4 last:border-0">
                <div class="flex min-w-0 items-center gap-3">
                    <i class="fas fa-key text-slate-400" aria-hidden="true"></i>
                    <div class="min-w-0">
                        <div class="truncate font-semibold text-slate-800">{{ $passkey->alias ?: 'Passkey' }}</div>
                        <div class="text-xs text-slate-500">
                            Added {{ $passkey->created_at?->timezone(config('app.timezone'))->format('d M Y') }}
                            @if($passkey->updated_at && $passkey->updated_at->gt($passkey->created_at->copy()->addMinute()))
                                | Last used {{ $passkey->updated_at->diffForHumans() }}
                            @endif
                        </div>
                    </div>
                </div>
                <form method="POST" action="{{ route('passkeys.destroy', $passkey->id) }}" onsubmit="return confirm('Remove this passkey? It will no longer sign in to your account.')">
                    @csrf
                    @method('DELETE')
                    <button class="text-sm font-semibold text-red-600 hover:text-red-800"><i class="fas fa-trash-can mr-1"></i>Remove</button>
                </form>
            </div>
        @empty
            <p class="px-5 py-4 text-sm text-slate-500">No passkeys yet. Add one above to sign in with your fingerprint or face next time.</p>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/passkeys.js') }}?v={{ filemtime(public_path('js/passkeys.js')) }}"></script>
<script>
(function () {
    var form = document.getElementById('passkey-form');
    var button = document.getElementById('passkey-button');
    var message = document.getElementById('passkey-message');

    function show(text, ok) {
        message.textContent = text;
        message.className = 'mb-4 rounded p-3 ' + (ok ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-700');
    }

    if (!window.Passkeys || !Passkeys.supported()) {
        form.classList.add('hidden');
        document.getElementById('passkey-unsupported').classList.remove('hidden');
        return;
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        button.disabled = true;
        button.querySelector('span').textContent = 'Waiting for your device...';
        message.className = 'mb-4 hidden rounded p-3';

        Passkeys.register(document.getElementById('passkey-alias').value.trim()).then(function (result) {
            if (result.ok) {
                show(result.message, true);
                setTimeout(function () { window.location.reload(); }, 900);
                return;
            }
            button.disabled = false;
            button.querySelector('span').textContent = 'Add passkey';
            if (result.message) show(result.message, false);
        });
    });
})();
</script>
@endpush
