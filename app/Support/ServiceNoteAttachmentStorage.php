<?php

namespace App\Support;

use App\Models\ServiceNote;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class ServiceNoteAttachmentStorage
{
    public const DISK = 'attachments';

    public const MAX_BYTES = 10485760;

    public static function store(ServiceNote $note, UploadedFile $file): void
    {
        self::deleteFile($note);

        $extension = strtolower($file->getClientOriginalExtension());
        $safeBase = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        if ($safeBase === '') {
            $safeBase = 'attachment';
        }

        $filename = $note->id.'_'.Str::uuid()->toString().'_'.$safeBase;
        if ($extension !== '') {
            $filename .= '.'.$extension;
        }

        $path = 'service-notes/'.$note->contact_id.'/'.$filename;

        Storage::disk(self::DISK)->putFileAs(
            'service-notes/'.$note->contact_id,
            $file,
            $filename,
        );

        $note->forceFill([
            'attachment_path' => $path,
            'attachment_original_name' => $file->getClientOriginalName(),
            'attachment_mime' => $file->getClientMimeType(),
            'attachment_size' => $file->getSize(),
        ])->save();
    }

    public static function deleteFile(ServiceNote $note): void
    {
        if ($note->attachment_path !== null && $note->attachment_path !== '') {
            Storage::disk(self::DISK)->delete($note->attachment_path);
        }

        $note->forceFill([
            'attachment_path' => null,
            'attachment_original_name' => null,
            'attachment_mime' => null,
            'attachment_size' => null,
        ])->save();
    }

    public static function fullPath(ServiceNote $note): ?string
    {
        if (! $note->hasAttachment()) {
            return null;
        }

        return Storage::disk(self::DISK)->path($note->attachment_path);
    }
}
