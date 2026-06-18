@if($note->hasAttachment())
    <p class="text-sm mb-3 flex flex-wrap items-center gap-x-3 gap-y-1">
        <span class="text-gray-700">{{ $note->attachment_original_name }}</span>
        @if($note->canPreviewAttachment())
            <button
                type="button"
                class="text-blue-600 hover:text-blue-800 font-medium"
                data-service-note-preview
                data-preview-url="{{ route('service-notes.attachment.preview', $note) }}"
                data-preview-kind="{{ $note->attachmentPreviewKind() }}"
                data-preview-filename="{{ $note->attachment_original_name }}"
                data-download-url="{{ route('service-notes.attachment', $note) }}"
            >
                Preview
            </button>
        @endif
        <a href="{{ route('service-notes.attachment', $note) }}" class="text-blue-600 hover:text-blue-800 font-medium">
            Download
        </a>
    </p>
@endif
