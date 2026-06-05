<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
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
        return [
            'company_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'telephone_1' => ['nullable', 'string', 'max:255'],
            'telephone_2' => ['nullable', 'string', 'max:255'],
            'email_address' => ['nullable', 'email', 'max:255'],
            'physical_address' => ['nullable', 'string', 'max:5000'],
            'related_contact_id' => ['nullable', 'integer', Rule::exists('contacts', 'id')],
            'contact_category_id' => ['nullable', 'integer', Rule::exists('contact_categories', 'id')],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $hasIdentifier = trim((string) $this->input('company_name')) !== ''
                || trim((string) $this->input('first_name')) !== ''
                || trim((string) $this->input('surname')) !== ''
                || trim((string) $this->input('telephone_1')) !== ''
                || trim((string) $this->input('telephone_2')) !== ''
                || trim((string) $this->input('email_address')) !== '';

            if (! $hasIdentifier) {
                $validator->errors()->add('company_name', 'Enter at least a company name, a person name, a phone number, or an email address.');
            }
        });
    }
}
