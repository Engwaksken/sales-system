<?php

namespace App\Policies;

use App\Models\ChatConversation;
use App\Models\NotificationInbox;
use App\Models\User;

class ChatConversationPolicy
{
    /**
     * Gate::authorize('customerView', $conversation);
     * Customer message retrieval requires ownership, not just a conversation ID.
     * Staff endpoints must retain their separate manage_chats permission guard.
     */
    public function customerView(User $user, ChatConversation $conversation): bool
    {
        return NotificationInbox::canOwnCustomerChat($user, $conversation);
    }

    /** Gate::authorize('customerNotificationRead', $conversation); */
    public function customerNotificationRead(User $user, ChatConversation $conversation): bool
    {
        return NotificationInbox::canOwnCustomerChat($user, $conversation);
    }
}
