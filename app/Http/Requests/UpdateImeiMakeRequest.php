<?php

namespace App\Http\Requests;

use App\Models\ImeiMake;
use App\Support\ImeiReferenceText;
use Illuminate\Foundation\Http\FormRequest;

class UpdateImeiMakeRequest extends FormRequest
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
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        /** @var ImeiMake $imeiMake */
        $imeiMake = $this->route('imeiMake');

        return [
            'make' => [
                'required',
                'string',
                'max:65535',
                function (string $attribute, mixed $value, \Closure $fail) use ($imeiMake): void {
                    if (ImeiReferenceText::equals($imeiMake->make, (string) $value)) {
                        return;
                    }

                    if (ImeiReferenceText::makeExists((string) $value)) {
                        $fail('A make with this name already exists.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'make.unique' => 'A make with this name already exists.',
        ];
    }
}
