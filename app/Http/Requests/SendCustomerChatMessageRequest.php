<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SendCustomerChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('customerView', $this->route('conversation')) ?? false;
    }

    public function rules(): array
    {
        return [
            'message' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $validator->errors()->has('message')
                && trim((string) $this->input('message', '')) === ''
                && ! $this->hasFile('attachment')) {
                $validator->errors()->add('message', 'Message or attachment is required.');
            }
        });
    }
}
