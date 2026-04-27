<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'required', 'string', 'max:120'],
            'email'       => ['sometimes', 'required', 'email', 'max:255', 'unique:users,email,' . $this->user()->id],
            'username'    => ['sometimes', 'required', 'string', 'max:50', 'unique:users,username,' . $this->user()->id],
            'avatar_file' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'El nombre es obligatorio.',
            'name.max'            => 'El nombre no puede superar 120 caracteres.',
            'avatar_file.image'   => 'El archivo debe ser una imagen.',
            'avatar_file.max'     => 'La imagen no puede superar 2 MB.',
            'email.unique'        => 'Este correo ya está en uso.',
            'username.unique'     => 'Este nombre de usuario ya está en uso.',
        ];
    }
}
