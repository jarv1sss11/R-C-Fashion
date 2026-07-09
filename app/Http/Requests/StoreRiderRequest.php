<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unique = 'unique:riders,email';
        if ($this->route('rider')) {
            $unique .= ',' . $this->route('rider')->id;
        }

        return [
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', $unique],
            'phone'        => ['required', 'string', 'max:20'],
            'vehicle_type' => ['required', 'in:motorcycle,bicycle,van'],
            'number_plate' => ['nullable', 'string', 'max:20'],
            'available'    => ['boolean'],
            'status'       => ['required', 'in:active,inactive,suspended'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ];
    }
}
