<?php

use App\Models\ServiceNote;
use App\Support\ServiceNoteAttachmentPreview;

test('detects image attachment preview kind', function () {
    $note = ServiceNote::factory()->make([
        'attachment_path' => 'service-notes/1/test.jpg',
        'attachment_original_name' => 'photo.jpg',
        'attachment_mime' => 'image/jpeg',
    ]);

    expect(ServiceNoteAttachmentPreview::canPreview($note))->toBeTrue();
    expect(ServiceNoteAttachmentPreview::previewKind($note))->toBe(ServiceNoteAttachmentPreview::KIND_IMAGE);
});

test('detects pdf attachment preview kind', function () {
    $note = ServiceNote::factory()->make([
        'attachment_path' => 'service-notes/1/test.pdf',
        'attachment_original_name' => 'invoice.pdf',
        'attachment_mime' => 'application/pdf',
    ]);

    expect(ServiceNoteAttachmentPreview::previewKind($note))->toBe(ServiceNoteAttachmentPreview::KIND_PDF);
});

test('detects office attachment preview kinds from extension', function (string $filename, string $kind) {
    $note = ServiceNote::factory()->make([
        'attachment_path' => 'service-notes/1/'.$filename,
        'attachment_original_name' => $filename,
        'attachment_mime' => 'application/octet-stream',
    ]);

    expect(ServiceNoteAttachmentPreview::previewKind($note))->toBe($kind);
})->with([
    'word' => ['report.docx', ServiceNoteAttachmentPreview::KIND_WORD],
    'excel' => ['sheet.xlsx', ServiceNoteAttachmentPreview::KIND_EXCEL],
    'powerpoint' => ['slides.pptx', ServiceNoteAttachmentPreview::KIND_POWERPOINT],
]);

test('does not preview unsupported attachment types', function () {
    $note = ServiceNote::factory()->make([
        'attachment_path' => 'service-notes/1/archive.zip',
        'attachment_original_name' => 'archive.zip',
        'attachment_mime' => 'application/zip',
    ]);

    expect(ServiceNoteAttachmentPreview::canPreview($note))->toBeFalse();
    expect(ServiceNoteAttachmentPreview::previewKind($note))->toBeNull();
});
