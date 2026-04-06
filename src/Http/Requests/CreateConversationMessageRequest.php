<?php

namespace NettSite\Messenger\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateConversationMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:1000'],
        ];
    }
}
