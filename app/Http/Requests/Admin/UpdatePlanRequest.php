<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:80'],
            'slug'             => ['required', 'string', 'max:80', 'unique:subscription_plans,slug,' . $this->route('id')],
            'description'      => ['nullable', 'string'],
            'features'         => ['nullable', 'array'],
            'price_usd'        => ['required', 'numeric', 'min:0'],
            'price_pen'        => ['nullable', 'numeric', 'min:0'],
            'duration_days'    => ['required', 'integer', 'min:1'],
            'discount_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active'        => ['boolean'],
            'is_visible'       => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'El nombre del plan es obligatorio.',
            'price_usd.required'     => 'El precio es obligatorio.',
            'price_usd.min'          => 'El precio no puede ser negativo.',
            'duration_days.required' => 'La duración en días es obligatoria.',
        ];
    }
}
