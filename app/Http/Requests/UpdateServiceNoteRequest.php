<?php

namespace App\Http\Requests;

use App\Models\ServiceNote;
use App\Support\ServiceNoteAttachmentStorage;
use App\Support\ServiceNoteRelatedLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceNoteRequest extends FormRequest
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
        /** @var ServiceNote $serviceNote */
        $serviceNote = $this->route('serviceNote');

        return array_merge([
            'note_type_id' => ['required', 'integer', Rule::exists('note_types', 'id')],
            'status' => ['required', 'string', Rule::in([ServiceNote::STATUS_OPEN, ServiceNote::STATUS_CLOSED])],
            'heading' => ['required', 'string', 'max:255'],
            'noted_at' => ['prohibited'],
            'body' => ['nullable', 'string', 'max:2000'],
            'attachment' => [
                'nullable',
                'file',
                'max:'.(int) (ServiceNoteAttachmentStorage::MAX_BYTES / 1024),
            ],
            'remove_attachment' => ['nullable', 'boolean'],
        ], ServiceNoteRelatedLink::rules((int) $serviceNote->contact_id, (int) $serviceNote->id));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note_type_id.required' => 'Please select a note type.',
            'note_type_id.exists' => 'Please select a valid note type.',
            'primary_service_note_id.exists' => 'Please select a valid related service note for this contact.',
        ];
    }
}
