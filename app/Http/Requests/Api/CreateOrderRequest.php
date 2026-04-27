<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled via auth:sanctum middleware
    }

    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', 'in:USD,PEN'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'payment_method' => ['required', 'string', 'in:wallet,card,bank_transfer,yape,plin'],
            'coupon_code' => ['sometimes', 'nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'currency.required' => 'Selecciona una moneda (USD o PEN).',
            'items.required' => 'El carrito no puede estar vacío.',
            'items.*.product_id.exists' => 'Uno o más productos no existen.',
            'payment_method.required' => 'Selecciona un método de pago.',
        ];
    }
}
