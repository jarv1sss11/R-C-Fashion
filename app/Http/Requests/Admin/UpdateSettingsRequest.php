<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:255'],
            'delivery_cost_standard' => ['required', 'numeric', 'min:0'],
            'delivery_cost_express' => ['required', 'numeric', 'min:0'],
            'recommendation_weight_content' => ['required', 'numeric', 'min:0', 'max:1'],
            'recommendation_weight_collaborative' => ['required', 'numeric', 'min:0', 'max:1'],
            'recommendation_weight_popularity' => ['required', 'numeric', 'min:0', 'max:1'],
            'maintenance_mode' => ['nullable', 'boolean'],
        ];
    }
}
