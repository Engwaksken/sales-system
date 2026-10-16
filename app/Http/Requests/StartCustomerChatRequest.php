<?php

namespace App\Http\Requests;

use App\Models\ChatConversation;
use Illuminate\Foundation\Http\FormRequest;

class StartCustomerChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The policy supports unsaved conversations and requires a customer identity.
        return $this->user()?->can('customerView', new ChatConversation([
            'email' => $this->customerEmail(),
        ])) ?? false;
    }

    public function customerEmail(): string
    {
        return strtolower(trim((string) $this->user()?->email));
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => $this->customerEmail()]);
    }

    public function rules(): array
    {
        // Submitted email is overwritten before validation; identity comes from the session.
        return [
            'email' => ['required', 'email', 'max:150'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'message' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
