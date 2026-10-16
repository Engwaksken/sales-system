<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReadOrderNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The controller authorizes the bound order before calling the helper.
        return true;
    }

    public function rules(): array
    {
        return ['notification_version' => ['required', 'integer', 'min:1']];
    }
}
