<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isRoleManager() === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'user_roles' => ['required', 'array'],
            'user_roles.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'user_roles.*.roles' => ['nullable', 'array'],
            'user_roles.*.roles.*' => ['integer', 'exists:roles,id'],
        ];
    }
}
