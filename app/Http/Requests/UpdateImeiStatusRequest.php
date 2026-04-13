<?php

namespace App\Http\Requests;

use App\Models\ImeiStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateImeiStatusRequest extends FormRequest
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
        /** @var ImeiStatus $imeiStatus */
        $imeiStatus = $this->route('imeiStatus');

        return [
            'status' => [
                'required',
                'string',
                'max:65535',
                Rule::unique('imei_statuses', 'status')->ignore($imeiStatus->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.unique' => 'A status with this name already exists.',
        ];
    }
}
