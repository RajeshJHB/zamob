<?php

namespace App\Support;

use App\Models\ServiceNote;

final class ServiceNoteAttachmentPreview
{
    public const KIND_IMAGE = 'image';

    public const KIND_PDF = 'pdf';

    public const KIND_TEXT = 'text';

    public const KIND_WORD = 'word';

    public const KIND_EXCEL = 'excel';

    public const KIND_POWERPOINT = 'powerpoint';

    public static function canPreview(ServiceNote $note): bool
    {
        return self::previewKind($note) !== null;
    }

    public static function previewKind(ServiceNote $note): ?string
    {
        if (! $note->hasAttachment()) {
            return null;
        }

        $mime = strtolower(trim((string) $note->attachment_mime));
        $extension = self::extension($note);

        if (self::isImage($mime, $extension)) {
            return self::KIND_IMAGE;
        }

        if (self::isPdf($mime, $extension)) {
            return self::KIND_PDF;
        }

        if (self::isText($mime, $extension)) {
            return self::KIND_TEXT;
        }

        if (self::isWord($mime, $extension)) {
            return self::KIND_WORD;
        }

        if (self::isExcel($mime, $extension)) {
            return self::KIND_EXCEL;
        }

        if (self::isPowerPoint($mime, $extension)) {
            return self::KIND_POWERPOINT;
        }

        return null;
    }

    private static function extension(ServiceNote $note): string
    {
        $name = (string) $note->attachment_original_name;

        if ($name === '') {
            return '';
        }

        return strtolower(pathinfo($name, PATHINFO_EXTENSION));
    }

    private static function isImage(string $mime, string $extension): bool
    {
        if (str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml') {
            return true;
        }

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);
    }

    private static function isPdf(string $mime, string $extension): bool
    {
        return $mime === 'application/pdf' || $extension === 'pdf';
    }

    private static function isText(string $mime, string $extension): bool
    {
        if (str_starts_with($mime, 'text/')) {
            return true;
        }

        return in_array($extension, ['txt', 'csv', 'log', 'md'], true);
    }

    private static function isWord(string $mime, string $extension): bool
    {
        if ($extension === 'doc') {
            return false;
        }

        return in_array($mime, [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
        ], true) || $extension === 'docx';
    }

    private static function isExcel(string $mime, string $extension): bool
    {
        return in_array($mime, [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
        ], true) || in_array($extension, ['xlsx', 'xls'], true);
    }

    private static function isPowerPoint(string $mime, string $extension): bool
    {
        if ($extension === 'ppt') {
            return false;
        }

        return in_array($mime, [
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-powerpoint',
        ], true) || $extension === 'pptx';
    }
}
