<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;

class ActivateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'machine_name' => ['nullable', 'string', 'max:120'],
            'os'           => ['nullable', 'string', 'max:80'],
            'device_type'  => ['nullable', 'string', 'in:desktop,laptop,mobile,tablet,other'],
            'machine_id'   => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_type.in' => 'Tipo de dispositivo no válido.',
        ];
    }
}
