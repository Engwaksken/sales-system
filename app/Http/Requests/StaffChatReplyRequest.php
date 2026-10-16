<?php

namespace App\Http\Requests;

class StaffChatReplyRequest extends SendCustomerChatMessageRequest
{
    public function authorize(): bool
    {
        // Staff routes retain their manage_chats permission middleware.
        return true;
    }
}
