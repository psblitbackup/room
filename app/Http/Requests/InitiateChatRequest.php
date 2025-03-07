<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateChatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'visitor_name' => 'required|string|max:255',
            'visitor_phone' => 'required|string|max:20',
            'visitor_email' => 'nullable|email|max:255',
        ];
    }
}
