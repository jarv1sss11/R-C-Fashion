<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
                Rule::notIn([$this->route('category')?->id]),
            ],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
