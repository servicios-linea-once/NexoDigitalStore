<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class CapturePayPalOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paypal_order_id' => ['required', 'string'],
            'order_ulid'      => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'paypal_order_id.required' => 'El ID de orden PayPal es obligatorio.',
            'order_ulid.required'      => 'La referencia de la orden es obligatoria.',
        ];
    }
}
