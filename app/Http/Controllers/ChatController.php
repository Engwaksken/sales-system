<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    // ---------------- Public customer/storefront ----------------

    public function start(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'required|string|max:30',
            'branch_id' => 'nullable|exists:branches,id',
            'message' => 'nullable|string|max:5000',
        ]);

        $conversation = ChatConversation::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'branch_id' => $data['branch_id'] ?? null,
            'status' => 'open',
        ]);

        if (! empty($data['message'])) {
            $conversation->messages()->create([
                'sender_type' => 'customer',
                'message' => $data['message'],
                'is_read' => false,
            ]);
        }

        return response()->json([
            'conversation_id' => $conversation->id,
        ]);
    }

    public function sendMessage(
        Request $request,
        ChatConversation $conversation
    ) {
        $data = $request->validate([
            'message' => 'nullable|string|max:5000',
            'attachment' => 'nullable|file|max:10240',
        ]);

        if (
            empty(trim((string) ($data['message'] ?? ''))) &&
            ! $request->hasFile('attachment')
        ) {
            return response()->json([
                'message' => 'Message or attachment is required.',
            ], 422);
        }

        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')
                ->store('chat-attachments', 'public')
            : null;

        $conversation->messages()->create([
            'sender_type' => 'customer',
            'message' => $data['message'] ?? null,
            'attachment_path' => $attachmentPath,
            'is_read' => false,
        ]);

        if ($conversation->status === 'closed') {
            $conversation->update(['status' => 'open']);
        }

        return response()->json(['status' => 'ok']);
    }

    public function messages(ChatConversation $conversation)
    {
        $messages = $conversation->messages()
            ->orderBy('id')
            ->get()
            ->map(fn ($message) => $this->messagePayload(
                $message,
                $conversation
            ));

        return response()->json([
            'messages' => $messages,
            'status' => $conversation->status,
        ]);
    }

    // ---------------- Staff support ----------------

    public function index(Request $request)
    {
        /*
         * A missing/old chat migration should not crash the entire support page.
         * Show an empty, actionable state instead.
         */
        if (
            ! Schema::hasTable('chat_conversations') ||
            ! Schema::hasTable('chat_messages')
        ) {
            return view('chat.index', [
                'conversations' => new \Illuminate\Pagination\LengthAwarePaginator(
                    collect(),
                    0,
                    $this->perPage($request),
                    1,
                    ['path' => $request->url()]
                ),
                'chatSetupMissing' => true,
            ]);
        }

        $perPage = $this->perPage($request);

        /*
         * Use a subquery instead of withCount() so the page does not depend on
         * a particular relationship method name (messages vs chat_messages).
         */
        $conversations = ChatConversation::query()
            ->select('chat_conversations.*')
            ->selectSub(
                DB::table('chat_messages')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'chat_messages.conversation_id',
                        'chat_conversations.id'
                    )
                    ->where('chat_messages.sender_type', 'customer')
                    ->where('chat_messages.is_read', false),
                'unread_count'
            )
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->query('status'));
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $search = '%'.trim((string) $request->query('q')).'%';

                $q->where(function ($sub) use ($search) {
                    $sub->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('phone', 'like', $search);
                });
            })
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('chat.index', [
            'conversations' => $conversations,
            'chatSetupMissing' => false,
        ]);
    }

    public function show(ChatConversation $conversation)
    {
        $conversation->load([
            'messages' => fn ($q) => $q->with('user')->orderBy('id'),
        ]);

        $conversation->messages()
            ->where('sender_type', 'customer')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('chat.show', compact('conversation'));
    }

    public function staffMessages(ChatConversation $conversation)
    {
        $conversation->messages()
            ->where('sender_type', 'customer')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = $conversation->messages()
            ->with('user')
            ->orderBy('id')
            ->get()
            ->map(fn ($message) => $this->messagePayload(
                $message,
                $conversation
            ));

        return response()->json([
            'messages' => $messages,
            'status' => $conversation->status,
        ]);
    }

    public function reply(
        Request $request,
        ChatConversation $conversation
    ) {
        $data = $request->validate([
            'message' => 'nullable|string|max:5000',
            'attachment' => 'nullable|file|max:10240',
        ]);

        if (
            empty(trim((string) ($data['message'] ?? ''))) &&
            ! $request->hasFile('attachment')
        ) {
            return back()->withErrors([
                'message' => 'Message or attachment is required.',
            ]);
        }

        $attachmentPath = $request->hasFile('attachment')
            ? $request->file('attachment')
                ->store('chat-attachments', 'public')
            : null;

        $conversation->messages()->create([
            'sender_type' => 'staff',
            'user_id' => auth()->id(),
            'message' => $data['message'] ?? null,
            'attachment_path' => $attachmentPath,
            'is_read' => false,
        ]);

        if ($conversation->status === 'closed') {
            $conversation->update(['status' => 'open']);
        }

        return redirect()
            ->route('chat.show', $conversation->id)
            ->with('status', 'Reply sent.');
    }

    public function close(ChatConversation $conversation)
    {
        $conversation->update(['status' => 'closed']);

        return redirect()
            ->route('chat.index')
            ->with('status', 'Conversation closed.');
    }

    private function messagePayload(
        ChatMessage $message,
        ChatConversation $conversation
    ): array {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'sender_name' => $message->sender_type === 'staff'
                ? ($message->user?->name ?? 'Support')
                : trim($conversation->first_name.' '.$conversation->last_name),
            'message' => $message->message,
            'attachment_url' => $message->attachment_path
                ? asset('storage/'.ltrim($message->attachment_path, '/'))
                : null,
            'created_at' => optional($message->created_at)->format('H:i'),
        ];
    }
}
