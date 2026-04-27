<?php

namespace App\Http\Requests\Reviews;

use Illuminate\Foundation\Http\FormRequest;

class VoteReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vote' => ['required', 'in:helpful,not_helpful'],
        ];
    }

    public function messages(): array
    {
        return [
            'vote.required' => 'El voto es obligatorio.',
            'vote.in'       => 'El voto debe ser "helpful" o "not_helpful".',
        ];
    }
}
