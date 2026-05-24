<?php

namespace App\Http\Requests;

use App\Models\ServiceNote;
use App\Support\ServiceNoteAttachmentStorage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceNoteRequest extends FormRequest
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
            'note_type_id' => ['required', 'integer', Rule::exists('note_types', 'id')],
            'status' => ['required', 'string', Rule::in([ServiceNote::STATUS_OPEN, ServiceNote::STATUS_CLOSED])],
            'heading' => ['required', 'string', 'max:255'],
            'noted_at' => ['prohibited'],
            'body' => ['required', 'string', 'max:2000'],
            'attachment' => [
                'nullable',
                'file',
                'max:'.(int) (ServiceNoteAttachmentStorage::MAX_BYTES / 1024),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note_type_id.required' => 'Please select a note type.',
            'note_type_id.exists' => 'Please select a valid note type.',
        ];
    }
}
