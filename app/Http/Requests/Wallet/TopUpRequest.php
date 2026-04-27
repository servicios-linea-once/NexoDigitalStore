<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class TopUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nt_amount'      => ['required', 'integer', 'min:10'],
            'payment_method' => ['required', 'string', 'in:paypal,mercadopago'],
        ];
    }

    public function messages(): array
    {
        return [
            'nt_amount.required'      => 'La cantidad de NT es obligatoria.',
            'nt_amount.integer'       => 'La cantidad debe ser un número entero.',
            'nt_amount.min'           => 'La cantidad mínima es 10 NT.',
            'payment_method.required' => 'Selecciona un método de pago.',
            'payment_method.in'       => 'Método de pago no válido.',
        ];
    }
}
