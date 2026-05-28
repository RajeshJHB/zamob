<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class ServiceNoteRelatedLink
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(int $contactId, ?int $excludeNoteId = null): array
    {
        return [
            'primary_service_note_id' => [
                'nullable',
                'integer',
                Rule::exists('service_notes', 'id')->where(function ($query) use ($contactId, $excludeNoteId) {
                    $query->where('contact_id', $contactId);
                    if ($excludeNoteId !== null) {
                        $query->where('id', '!=', $excludeNoteId);
                    }
                }),
            ],
        ];
    }
}
