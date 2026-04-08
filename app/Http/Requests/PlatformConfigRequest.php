<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlatformConfigRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],

            // Whether to send an auto reply
            'send_auto_reply' => ['required', 'boolean'],

            // The method to be used for auto reply
            'auto_reply_method' => ['required_if:send_auto_reply,true'],
            'auto_reply_method_name' => ['nullable'],

            'auto_reply_dataset' => ['required_unless:auto_reply_method,default', 'nullable'],
            'auto_reply_dataset_name' => ['nullable'],

            // Whether to send a welcome message
            'send_welcome_message' => ['required', 'boolean'],

            // The welcome message template
            'welcome_message_template' => [
                'required_if:send_welcome_message,true',
                'nullable',
                'max:2000',
            ],
        ];

        if (in_array($this->input('module'), ['whatsapp', 'telegram'])) {
            $rules['access_token'] = ['required', 'string', 'max:2000'];
        }

        return $rules;
    }
}
