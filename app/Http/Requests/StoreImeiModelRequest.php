<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreImeiModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'make' => ['required', 'string', 'max:65535', Rule::exists('imei_make', 'make')],
            'model' => ['required', 'string', 'max:65535'],
            'serial' => ['nullable', 'string', 'max:65535'],
        ];
    }
}
