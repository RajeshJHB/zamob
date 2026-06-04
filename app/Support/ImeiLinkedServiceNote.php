<?php

namespace App\Support;

use App\Models\Imei;
use App\Models\ServiceNote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class ImeiLinkedServiceNote
{
    public const BODY_IMEI_LINE_PREFIX = 'IMEI: ';

    public const BODY_MAX_LENGTH = 2000;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function validationRules(): array
    {
        return [
            'linked_service_note_id' => ['nullable', 'integer', Rule::exists('service_notes', 'id')],
            'close_linked_service_note' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function stripFromImeiPayload(array $data): array
    {
        unset($data['linked_service_note_id'], $data['close_linked_service_note']);

        return $data;
    }

    public static function applyFromImeiSave(Request $request, Imei $imei): void
    {
        if (! $request->boolean('close_linked_service_note')) {
            return;
        }

        $noteId = (int) $request->input('linked_service_note_id', 0);
        if ($noteId <= 0) {
            return;
        }

        /** @var ServiceNote|null $note */
        $note = ServiceNote::query()->find($noteId);
        if ($note === null) {
            return;
        }

        $user = $request->user();
        $updates = [
            'body' => self::appendImeiToBody((string) $note->body, (string) $imei->imei),
            'staff' => ImeiStaffAudit::appendEmail(
                (string) $note->staff,
                $user instanceof User ? (string) $user->email : '',
            ),
        ];

        if (! $note->isClosed()) {
            $updates['status'] = ServiceNote::STATUS_CLOSED;
        }

        $note->update($updates);
    }

    public static function appendImeiToBody(string $body, string $imei): string
    {
        $imei = trim($imei);
        if ($imei === '') {
            return self::truncateBody($body);
        }

        $line = self::BODY_IMEI_LINE_PREFIX.$imei;
        $body = trim($body);

        if ($body === '') {
            return self::truncateBody($line);
        }

        if (str_contains($body, $line)) {
            return self::truncateBody($body);
        }

        return self::truncateBody($body."\n\n".$line);
    }

    private static function truncateBody(string $body): string
    {
        if (strlen($body) <= self::BODY_MAX_LENGTH) {
            return $body;
        }

        return substr($body, 0, self::BODY_MAX_LENGTH);
    }
}
