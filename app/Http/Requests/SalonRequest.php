<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SalonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'status' => 'required|string',
            'user_id' => 'required|string',
        ];
    }
}
