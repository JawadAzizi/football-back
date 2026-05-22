<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReservationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return [
            'slot_id' => 'required|string',
            'user_id' => 'required|string',
        ]; }
}