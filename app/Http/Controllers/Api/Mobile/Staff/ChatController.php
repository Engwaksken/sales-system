<?php

namespace App\Http\Controllers\Api\Mobile\Staff;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Customer chat support for staff: conversations, replies and closing (manage_chats). */
class ChatController extends StaffController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'manage_chats');

        $query = ChatConversation::query()
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_type', 'customer')->where('is_read', false)])
            ->with(['messages' => fn ($q) => $q->latest('id')->limit(1)]);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"));
        }

        $page = $query->orderByDesc('updated_at')->orderByDesc('id')->paginate($this->perPage($request));

        return response()->json([
            'data' => collect($page->items())->map(fn (ChatConversation $c) => $this->summary($c) + [
                'last_message' => $c->messages->first()?->message,
                'unread_count' => (int) $c->unread_count,
            ])->all(),
            'meta' => $this->meta($page),
        ]);
    }

    public function show(Request $request, ChatConversation $conversation): JsonResponse
    {
        $this->authorizePermission($request, 'manage_chats');

        $conversation->messages()->where('sender_type', 'customer')->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['data' => $this->detail($conversation)]);
    }

    public function reply(Request $request, ChatConversation $conversation): JsonResponse
    {
        $this->authorizePermission($request, 'manage_chats');
        $data = $request->validate(['message' => 'required|string|max:5000']);

        $conversation->messages()->create([
            'sender_type' => 'staff',
            'user_id' => $request->user()->id,
            'message' => $data['message'],
            'is_read' => false,
        ]);
        if ($conversation->status === 'closed') {
            $conversation->update(['status' => 'open']);
        }
        $conversation->touch();

        return response()->json(['message' => 'Reply sent.', 'data' => $this->detail($conversation->fresh())], 201);
    }

    public function close(Request $request, ChatConversation $conversation): JsonResponse
    {
        $this->authorizePermission($request, 'manage_chats');
        $conversation->update(['status' => 'closed']);

        return response()->json(['message' => 'Conversation closed.', 'data' => $this->summary($conversation)]);
    }

    private function summary(ChatConversation $c): array
    {
        return [
            'id' => $c->id,
            'name' => trim($c->first_name.' '.$c->last_name) ?: ($c->email ?: 'Customer'),
            'email' => $c->email,
            'phone' => $c->phone,
            'status' => $c->status,
            'updated_at' => optional($c->updated_at)?->toISOString(),
        ];
    }

    private function detail(ChatConversation $c): array
    {
        return $this->summary($c) + [
            'messages' => $c->messages()->with('user:id,name')->orderBy('id')->get()->map(fn (ChatMessage $m) => [
                'id' => $m->id,
                'sender_type' => $m->sender_type,
                'sender_name' => $m->sender_type === 'staff' ? ($m->user?->name ?? 'Staff') : null,
                'message' => $m->message,
                'has_attachment' => filled($m->attachment_path),
                'created_at' => optional($m->created_at)?->toISOString(),
            ])->values(),
        ];
    }
}
