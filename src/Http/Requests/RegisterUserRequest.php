<?php

namespace NettSite\Messenger\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'fcm_token' => ['nullable', 'string'],
            'platform' => ['required', 'string', 'in:android,ios,web'],
        ];
    }
}
