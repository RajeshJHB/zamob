<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImeiLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\Unique>>
     */
    public function rules(): array
    {
        return [
            'location' => ['required', 'string', 'max:65535', 'unique:imei_locations,location'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'location.unique' => 'A location with this name already exists.',
        ];
    }
}
