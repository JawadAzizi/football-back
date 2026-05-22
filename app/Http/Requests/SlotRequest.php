<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SlotRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return [
            'date' => 'required|string|max:255',
            'time' => 'required|string|max:255',
            'status' => 'required|string',
            'salon_id' => 'required|string',
        ]; }
}