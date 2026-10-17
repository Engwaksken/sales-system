@extends('layouts.app')

@section('title', 'Chat Support')

@section('content')
<div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <div>
        <h1 class="text-xl font-black text-slate-800">
            <i class="fas fa-comments mr-2 text-indigo-600"></i>
            {{ trim($conversation->first_name.' '.$conversation->last_name) }}
        </h1>
        <div class="mt-1 text-sm text-slate-500">
            {{ $conversation->email }} · {{ $conversation->phone }}
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        @if($conversation->status === 'open')
            <form method="POST" action="{{ route('chat.close', $conversation->id) }}">
                @csrf
                <button class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-600">
                    <i class="fas fa-circle-check mr-1"></i>
                    Close Conversation
                </button>
            </form>
        @endif

        <a href="{{ route('chat.index') }}"
           class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-600">
            <i class="fas fa-arrow-left mr-1"></i>
            All Chats
        </a>
    </div>
</div>

@if(session('status'))
    <div data-flash class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
        {{ session('status') }}
    </div>
@endif

<div id="chat-thread"
     class="mb-4 flex h-[480px] flex-col gap-3 overflow-y-auto rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    @foreach($conversation->messages as $message)
        <div class="max-w-[80%] {{ $message->sender_type === 'staff' ? 'ml-auto text-right' : '' }}">
            <div class="inline-block rounded-xl px-3 py-2 text-sm
                {{ $message->sender_type === 'staff'
                    ? 'bg-indigo-600 text-white'
                    : 'bg-slate-100 text-slate-700' }}">
                @if($message->message)
                    <div>{{ $message->message }}</div>
                @endif

                @if($message->attachment_path)
                    <div class="mt-1">
                        <a href="{{ asset('storage/'.ltrim($message->attachment_path, '/')) }}"
                           target="_blank"
                           class="text-xs underline">
                            <i class="fas fa-paperclip mr-1"></i>
                            Attachment
                        </a>
                    </div>
                @endif
            </div>

            <div class="mt-1 text-xs text-slate-400">
                {{ optional($message->created_at)->format('H:i') }}
            </div>
        </div>
    @endforeach
</div>

@if($errors->any())
    <div data-flash class="mb-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST"
      action="{{ route('chat.reply', $conversation->id) }}"
      enctype="multipart/form-data"
      class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    @csrf

    <label class="mb-1 block text-sm font-bold text-slate-700">
        Reply
    </label>

    <textarea name="message"
              rows="3"
              placeholder="Type a helpful reply..."
              class="w-full rounded-lg border-slate-300 text-sm"></textarea>

    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <input type="file" name="attachment" class="text-xs">

        <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white">
            <i class="fas fa-paper-plane"></i>
            Send Reply
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const conversationId = @json($conversation->id);
    const thread = document.getElementById('chat-thread');

    if (!thread) return;

    thread.scrollTop = thread.scrollHeight;

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    async function pollThread() {
        try {
            const response = await fetch(`/chat-support/${conversationId}/messages`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) return;

            const data = await response.json();
            const wasAtBottom =
                thread.scrollTop + thread.clientHeight >= thread.scrollHeight - 24;

            thread.innerHTML = (data.messages || []).map(message => {
                const staff = message.sender_type === 'staff';

                return `
                    <div class="max-w-[80%] ${staff ? 'ml-auto text-right' : ''}">
                        <div class="inline-block rounded-xl px-3 py-2 text-sm ${staff ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-700'}">
                            ${message.message ? `<div>${escapeHtml(message.message)}</div>` : ''}
                            ${message.attachment_url
                                ? `<div class="mt-1"><a href="${encodeURI(message.attachment_url)}" target="_blank" class="text-xs underline"><i class="fas fa-paperclip mr-1"></i>Attachment</a></div>`
                                : ''}
                        </div>
                        <div class="mt-1 text-xs text-slate-400">${escapeHtml(message.created_at || '')}</div>
                    </div>
                `;
            }).join('');

            if (wasAtBottom) {
                thread.scrollTop = thread.scrollHeight;
            }
        } catch (_) {
            // Retry on next poll without crashing the page.
        }
    }

    setInterval(pollThread, 5000);
})();
</script>
@endpush
