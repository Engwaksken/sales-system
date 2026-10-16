<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReadAllNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Scope is validated here, then policy-authorized before any writes.
        return true;
    }

    public function rules(): array
    {
        return ['scope' => ['sometimes', 'required', Rule::in(['system', 'all'])]];
    }
}
