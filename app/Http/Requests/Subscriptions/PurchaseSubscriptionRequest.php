<?php

namespace App\Http\Requests\Subscriptions;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_slug'         => ['required', 'string', 'exists:subscription_plans,slug'],
            'payment_gateway'   => ['required', 'in:paypal,mercadopago,nexotokens'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_slug.required'       => 'Selecciona un plan.',
            'plan_slug.exists'         => 'El plan seleccionado no existe.',
            'payment_gateway.required' => 'Selecciona un método de pago.',
            'payment_gateway.in'       => 'Pasarela de pago no válida.',
        ];
    }
}
