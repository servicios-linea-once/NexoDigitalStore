<?php

namespace App\Http\Requests\Reviews;

use Illuminate\Foundation\Http\FormRequest;

class ReplyReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['seller', 'admin']);
    }

    public function rules(): array
    {
        return [
            'reply' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reply.required' => 'La respuesta es obligatoria.',
            'reply.max'      => 'La respuesta no puede superar 1000 caracteres.',
        ];
    }
}
