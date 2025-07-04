<?php

namespace Swoopy\LaracordLiveChat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartSessionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [
            'email.email' => 'Please provide a valid email address.',
            'name.max' => 'Name cannot be longer than 255 characters.',
            'email.max' => 'Email cannot be longer than 255 characters.',
        ];
    }
}
