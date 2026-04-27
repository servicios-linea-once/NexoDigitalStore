<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class ProcessCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method'    => ['required', 'in:paypal,mercadopago,nexotokens'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'currency'          => ['required', 'string', 'exists:currencies,code'],
            'nt_amount'         => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'Selecciona un método de pago.',
            'payment_method.in'       => 'Método de pago no válido.',
            'currency.required'       => 'La moneda es obligatoria.',
            'currency.exists'         => 'La moneda seleccionada no está soportada.',
        ];
    }
}
