<?php

namespace App\Http\Requests\Subscriptions;

use Illuminate\Foundation\Http\FormRequest;

class RenewSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_gateway'   => ['required', 'in:paypal,mercadopago,nexotokens'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_gateway.required' => 'Selecciona un método de pago.',
            'payment_gateway.in'       => 'Pasarela de pago no válida.',
        ];
    }
}
