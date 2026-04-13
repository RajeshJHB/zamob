<?php

namespace App\Http\Requests;

use App\Models\ImeiLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImeiLocationRequest extends FormRequest
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
        /** @var ImeiLocation $imeiLocation */
        $imeiLocation = $this->route('imeiLocation');

        return [
            'location' => [
                'required',
                'string',
                'max:65535',
                Rule::unique('imei_locations', 'location')->ignore($imeiLocation->id),
            ],
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
