<?php

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class DeliverKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['seller', 'admin']);
    }

    public function rules(): array
    {
        return [
            'key_value'     => ['required', 'string', 'max:500'],
            'delivery_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'key_value.required' => 'La clave digital es obligatoria.',
            'key_value.max'      => 'La clave no puede superar 500 caracteres.',
            'delivery_note.max'  => 'La nota no puede superar 500 caracteres.',
        ];
    }
}
