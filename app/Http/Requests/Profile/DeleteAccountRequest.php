<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required'        => 'Debes ingresar tu contraseña para confirmar.',
            'password.current_password' => 'La contraseña ingresada es incorrecta.',
        ];
    }
}
