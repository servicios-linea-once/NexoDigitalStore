<?php

namespace App\Http\Requests\Admin\Store\Keys;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportKeysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('keys.import') ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id'      => ['required', 'exists:products,id'],
            'license_type'    => ['required', Rule::in(['perpetual', 'subscription', 'trial'])],
            'max_activations' => ['required', 'integer', 'min:1', 'max:10'],
            'mode'            => ['nullable', Rule::in(['single', 'bulk'])],
            'key_value'       => ['required_if:mode,single', 'nullable', 'string', 'min:4', 'max:255'],
            'keys_text'       => ['required_without_all:keys_file,key_value', 'nullable', 'string'],
            'keys_file'       => ['nullable', 'file', 'mimes:txt,csv', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required'     => 'Selecciona un producto.',
            'license_type.required'   => 'Indica el tipo de licencia.',
            'max_activations.min'     => 'Las activaciones deben ser al menos 1.',
            'max_activations.max'     => 'Las activaciones no pueden superar 10.',
            'key_value.required_if'   => 'Ingresa la clave.',
            'keys_text.required_without_all' => 'Pega al menos una clave o adjunta un archivo.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        parent::failedValidation($validator);
    }
}
