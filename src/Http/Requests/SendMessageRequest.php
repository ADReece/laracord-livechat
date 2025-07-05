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
            'content' => 'required|string|max:2000', // Fixed: using 'content' instead of 'message'
            'sender_type' => 'required|in:customer,agent', // Added missing validation
            'sender_name' => 'nullable|string|max:255', // Fixed: using 'sender_name' instead of 'name'
        ];
    }

    public function messages()
    {
        return [
            'session_id.required' => 'Session ID is required.',
            'session_id.uuid' => 'Invalid session ID format.',
            'session_id.exists' => 'Chat session does not exist.',
            'content.required' => 'Message content is required.', // Fixed field name
            'content.max' => 'Message cannot be longer than 2000 characters.',
            'sender_type.required' => 'Sender type is required.',
            'sender_type.in' => 'Sender type must be either customer or agent.',
            'sender_name.max' => 'Name cannot be longer than 255 characters.',
        ];
    }
}
