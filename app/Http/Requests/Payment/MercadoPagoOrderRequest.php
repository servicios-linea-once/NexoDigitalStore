<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class MercadoPagoOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_ulid' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_ulid.required' => 'La referencia de la orden es obligatoria.',
        ];
    }
}
