<?php

namespace ADReece\LaracordLiveChat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'session_id' => 'required|uuid|exists:chat_sessions,id',
            'message' => 'required|string|max:2000',
            'name' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'session_id.required' => 'Session ID is required.',
            'session_id.uuid' => 'Invalid session ID format.',
            'session_id.exists' => 'Chat session does not exist.',
            'message.required' => 'Message content is required.',
            'message.max' => 'Message cannot be longer than 2000 characters.',
            'name.max' => 'Name cannot be longer than 255 characters.',
        ];
    }
}
