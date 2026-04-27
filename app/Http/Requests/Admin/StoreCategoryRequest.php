<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        // Extract ID from route for unique-except-self on slug
        // Works for both POST (no ID) and PUT/PATCH (with string $id)
        $categoryId = $this->route('id') ?? $this->route('category')?->id ?? 'NULL';

        return [
            'name'        => ['required', 'string', 'max:100'],
            'slug'        => ['nullable', 'string', 'max:120', "unique:categories,slug,{$categoryId}"],
            'parent_id'   => ['nullable', 'exists:categories,id'],
            'icon'        => ['nullable', 'string', 'max:100'],
            'color'       => ['nullable', 'string', 'max:20'],
            'sort_order'  => ['nullable', 'integer'],
            'is_active'   => ['boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.max'      => 'El nombre no puede superar 100 caracteres.',
            'slug.unique'   => 'Ya existe una categoría con ese slug.',
        ];
    }
}
