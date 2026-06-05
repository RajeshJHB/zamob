<?php

namespace App\Http\Requests;

use App\Models\ContactCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var ContactCategory $contactCategory */
        $contactCategory = $this->route('contactCategory');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('contact_categories', 'name')->ignore($contactCategory->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A contact category with this name already exists.',
        ];
    }
}
