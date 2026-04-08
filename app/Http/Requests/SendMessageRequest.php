<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'conversation_id' => ['required', 'exists:conversations,id'],
            'reply_message_id' => ['nullable'],
            'type' => ['required'],
            'text' => ['nullable', 'string'],
            'caption' => ['nullable', 'string', 'max:255'],
            'template' => ['nullable', 'array'],
            'attachments' => ['nullable', 'array'],
        ];
    }
}
