<?php

namespace ADReece\LaracordLiveChat\Http\Requests;

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
            'customer_name' => 'required|string|max:255', // Fixed: made required and using correct field name
            'customer_email' => 'required|email|max:255', // Fixed: made required and using correct field name
            'initial_message' => 'nullable|string|max:2000', // Added missing field
            'metadata' => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [
            'customer_name.required' => 'Customer name is required.',
            'customer_name.max' => 'Customer name cannot be longer than 255 characters.',
            'customer_email.required' => 'Customer email is required.',
            'customer_email.email' => 'Please provide a valid email address.',
            'customer_email.max' => 'Email cannot be longer than 255 characters.',
            'initial_message.max' => 'Initial message cannot be longer than 2000 characters.',
        ];
    }
}
