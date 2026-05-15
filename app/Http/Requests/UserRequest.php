<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'email_verified_at' => 'nullable|string',
            'password' => 'required|string|max:255',
            'token' => 'required|string|max:255',
            'user_id' => 'nullable|string',
            'user_agent' => 'nullable|string',
            'payload' => 'required|string',
            'last_activity' => 'required|integer',
        ]; }
}