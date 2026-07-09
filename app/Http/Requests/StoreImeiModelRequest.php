<?php

namespace App\Http\Requests;

use App\Support\ImeiReferenceText;
use Illuminate\Foundation\Http\FormRequest;

class StoreImeiModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('make') && is_string($this->input('make'))) {
            $this->merge(['make' => ImeiReferenceText::normalize($this->input('make'))]);
        }

        if ($this->has('model') && is_string($this->input('model'))) {
            $this->merge(['model' => ImeiReferenceText::normalize($this->input('model'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'make' => [
                'required',
                'string',
                'max:65535',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! ImeiReferenceText::makeExists((string) $value)) {
                        $fail('Please choose a valid make from the list.');
                    }
                },
            ],
            'model' => ['required', 'string', 'max:65535'],
            'serial' => ['nullable', 'string', 'max:65535'],
            'item_code' => ['nullable', 'string', 'max:65535'],
        ];
    }
}
