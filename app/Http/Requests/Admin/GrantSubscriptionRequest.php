<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GrantSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'user_search'     => ['required', 'string', 'max:255'],
            'plan_id'         => ['required', 'integer', 'exists:subscription_plans,id'],
            'expires_at'      => ['nullable', 'date', 'after:today'],
            'note'            => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_search.required' => 'El usuario es obligatorio.',
            'plan_id.required'     => 'El plan es obligatorio.',
            'plan_id.exists'       => 'El plan no existe.',
        ];
    }
}
