<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Http\Requests\StartCustomerChatRequest;
use App\Http\Requests\SendCustomerChatMessageRequest;
use App\Http\Requests\StaffChatReplyRequest;
use App\Http\Resources\ChatResultResource;
use App\Http\Resources\ChatMessagesResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    use AuthorizesRequests;

    // ---------------- Authenticated customer/storefront ----------------

    public function start(StartCustomerChatRequest $request)
    {
        $data = $request->validated();

        $conversation = ChatConversation::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $request->customerEmail(),
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

        return new ChatResultResource([
            'conversation_id' => $conversation->id,
        ]);
    }

    public function sendMessage(
        SendCustomerChatMessageRequest $request,
        ChatConversation $conversation
    ) {
        $data = $request->validated();

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

        return new ChatResultResource(['status' => 'ok']);
    }

    public function messages(ChatConversation $conversation)
    {
        $this->authorize('customerView', $conversation);

        $messages = $conversation->messages()
            ->with('user')
            ->orderBy('id')
            ->get();

        return new ChatMessagesResource([
            'messages' => $messages,
            'conversation' => $conversation,
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
        $messages = $conversation->messages()
            ->with('user')
            ->orderBy('id')
            ->get();

        return new ChatMessagesResource([
            'messages' => $messages,
            'conversation' => $conversation,
        ]);
    }

    public function reply(
        StaffChatReplyRequest $request,
        ChatConversation $conversation
    ) {
        $data = $request->validated();

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

    public function attachment(ChatMessage $message)
    {
        abort_unless($message->attachment_path, 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($message->attachment_path), 404);

        return $disk->response($message->attachment_path);
    }

}
