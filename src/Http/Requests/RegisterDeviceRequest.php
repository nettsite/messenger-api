<?php

namespace NettSite\Messenger\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDeviceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'platform' => ['required', 'string', 'in:android,ios'],
            'user_id' => ['nullable', 'string'],
        ];
    }
}
