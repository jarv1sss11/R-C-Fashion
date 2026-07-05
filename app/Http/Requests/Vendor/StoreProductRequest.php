<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The form collects sizes as a comma-separated string ("S, M, L") —
     * transform it into an array here so validation/storage both deal with
     * the same array shape the `sizes` JSON column expects.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('sizes') && is_string($this->input('sizes'))) {
            $this->merge([
                'sizes' => array_values(array_filter(array_map('trim', explode(',', $this->input('sizes'))))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'primary_color' => ['nullable', 'string', 'max:255'],
            'sizes' => ['nullable', 'array'],
            'sizes.*' => ['string', 'max:10'],
            'is_featured' => ['sometimes', 'boolean'],
            'status' => ['required', 'in:draft,published,archived'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'max:2048'],
        ];
    }
}
