<?php

namespace App\Http\Requests;

use App\Models\NoteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNoteTypeRequest extends FormRequest
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
        /** @var NoteType $noteType */
        $noteType = $this->route('noteType');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('note_types', 'name')->ignore($noteType->id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A note type with this name already exists.',
        ];
    }
}
