<?php

namespace App\Http\Requests\Reviews;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'rating'        => ['required', 'integer', 'min:1', 'max:5'],
            'title'         => ['nullable', 'string', 'max:120'],
            'comment'       => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_item_id.required' => 'El item de orden es obligatorio.',
            'order_item_id.exists'   => 'El item de orden no es válido.',
            'rating.required'        => 'La calificación es obligatoria.',
            'rating.min'             => 'La calificación mínima es 1 estrella.',
            'rating.max'             => 'La calificación máxima es 5 estrellas.',
            'comment.max'            => 'El comentario no puede superar 1000 caracteres.',
        ];
    }
}
