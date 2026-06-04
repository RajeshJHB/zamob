@extends('layouts.app')

@section('title', $note ? 'Edit service note' : 'New service note')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <div class="mb-4">
            <a href="{{ route('contacts.show', $contact) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">← {{ $contact->displayName() }}</a>
        </div>

        @include('contacts.partials.fields-display-compact', ['contact' => $contact, 'wrapperClass' => 'mb-4'])

        <h1 class="text-2xl font-bold {{ $note ? 'mb-2' : 'mb-4' }}">
            @if($note)
                Edit service note
            @elseif($selectedRelatedNote ?? null)
                New related service note
            @else
                New service note
            @endif
        </h1>
        @if($note)
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 mb-6 text-sm">
                <span class="font-mono font-semibold text-gray-800">{{ $note->formattedNoteNumber() }}</span>
                @include('contacts.service-notes.partials.times', ['note' => $note, 'class' => 'sm:justify-end'])
            </div>
        @endif

        <form method="POST" action="{{ $note ? route('service-notes.update', $note) : route('contacts.service-notes.store', $contact) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @if($note)
                @method('PUT')
            @endif

            @if(($relatedNotesForSelect ?? collect())->isNotEmpty())
                @include('contacts.service-notes.partials.related-note-form')
            @endif

            <div>
                <label for="note_type_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Note type <span class="text-red-600" aria-hidden="true">*</span>
                </label>
                <select
                    name="note_type_id"
                    id="note_type_id"
                    required
                    aria-required="true"
                    class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full @error('note_type_id') border-red-500 @enderror"
                >
                    <option value="" disabled @selected(old('note_type_id', $note->note_type_id ?? '') === '')>Select type…</option>
                    @foreach($noteTypes as $type)
                        <option value="{{ $type->id }}" @selected((int) old('note_type_id', $note->note_type_id ?? '') === $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
                @error('note_type_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" id="status" required class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full">
                    <option value="{{ \App\Models\ServiceNote::STATUS_OPEN }}" @selected(old('status', $note->status ?? \App\Models\ServiceNote::STATUS_OPEN) === \App\Models\ServiceNote::STATUS_OPEN)>Open</option>
                    <option value="{{ \App\Models\ServiceNote::STATUS_CLOSED }}" @selected(old('status', $note->status ?? '') === \App\Models\ServiceNote::STATUS_CLOSED)>Closed</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Any user can close a note. Only Role 4 can delete a closed note.</p>
            </div>

            <div>
                <label for="heading" class="block text-sm font-medium text-gray-700 mb-1">Heading</label>
                <input type="text" name="heading" id="heading" value="{{ old('heading', $note->heading ?? '') }}" maxlength="255" required class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full">
            </div>

            <div>
                <label for="body" class="block text-sm font-medium text-gray-700 mb-1">Note</label>
                <textarea name="body" id="body" rows="8" maxlength="2000" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full">{{ old('body', $note->body ?? '') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Optional when a heading is provided. Maximum 2000 characters.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Attachment (optional, one file, any type)</label>
                @if($note && $note->hasAttachment())
                    <p class="text-sm text-gray-600 mb-2">
                        Current:
                        <a href="{{ route('service-notes.attachment', $note) }}" class="text-blue-600 hover:text-blue-800 font-medium" target="_blank" rel="noopener noreferrer">{{ $note->attachment_original_name }}</a>
                    </p>
                    <label class="flex items-center gap-2 text-sm text-gray-700 mb-2">
                        <input type="checkbox" name="remove_attachment" value="1" {{ old('remove_attachment') ? 'checked' : '' }}>
                        Remove current attachment
                    </label>
                @endif
                <div
                    id="attachment-drop-zone"
                    class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors"
                    role="button"
                    tabindex="0"
                    aria-label="Upload attachment by clicking or dragging a file"
                >
                    <p class="text-sm text-gray-700 font-medium">Drag and drop a file here</p>
                    <p class="text-xs text-gray-500 mt-1">or click to browse — any file type, max 10 MB</p>
                    <p id="attachment-file-name" class="text-sm text-gray-800 mt-3 font-medium hidden"></p>
                </div>
                <input type="file" name="attachment" id="attachment" class="sr-only">
            </div>

            <div class="flex flex-wrap gap-3 pt-4">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">{{ $note ? 'Save' : 'Save service note' }}</button>
                <a href="{{ route('contacts.show', $contact) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded inline-block">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const isCreate = @json($note === null);
    const repairNoteTypeId = @json($repairNoteTypeId);
    const repairNoteHeadingTemplate = @json($repairNoteHeadingTemplate);
    const repairNoteBodyTemplate = @json($repairNoteBodyTemplate);
    const noteTypeSelect = document.getElementById('note_type_id');
    const headingInput = document.getElementById('heading');
    const bodyTextarea = document.getElementById('body');
    const serviceNoteForm = document.querySelector('form[action*="service-notes"]');

    if (serviceNoteForm && noteTypeSelect) {
        serviceNoteForm.addEventListener('submit', function (event) {
            if (noteTypeSelect.value === '') {
                event.preventDefault();
                noteTypeSelect.focus();
                noteTypeSelect.classList.add('border-red-500');
            }
        });

        noteTypeSelect.addEventListener('change', function () {
            if (noteTypeSelect.value !== '') {
                noteTypeSelect.classList.remove('border-red-500');
            }
        });
    }

    function shouldApplyRepairHeading() {
        if (!headingInput) {
            return false;
        }
        const current = headingInput.value;
        if (current.trim() === '') {
            return true;
        }

        return current === repairNoteHeadingTemplate;
    }

    function shouldApplyRepairTemplate() {
        if (!bodyTextarea) {
            return false;
        }
        const current = bodyTextarea.value;
        if (current.trim() === '') {
            return true;
        }

        return current === repairNoteBodyTemplate;
    }

    function applyRepairTemplateIfNeeded() {
        if (!isCreate || !noteTypeSelect || repairNoteTypeId === null) {
            return;
        }
        if (parseInt(noteTypeSelect.value, 10) !== repairNoteTypeId) {
            return;
        }
        if (headingInput && shouldApplyRepairHeading()) {
            headingInput.value = repairNoteHeadingTemplate;
        }
        if (bodyTextarea && shouldApplyRepairTemplate()) {
            bodyTextarea.value = repairNoteBodyTemplate;
        }
    }

    if (noteTypeSelect) {
        noteTypeSelect.addEventListener('change', applyRepairTemplateIfNeeded);
        applyRepairTemplateIfNeeded();
    }

    const dropZone = document.getElementById('attachment-drop-zone');
    const fileInput = document.getElementById('attachment');
    const fileNameEl = document.getElementById('attachment-file-name');

    if (!dropZone || !fileInput) {
        return;
    }

    function showSelectedFile(file) {
        if (!fileNameEl || !file) {
            return;
        }
        fileNameEl.textContent = 'Selected: ' + file.name;
        fileNameEl.classList.remove('hidden');
    }

    function assignFile(file) {
        if (!file) {
            return;
        }
        const transfer = new DataTransfer();
        transfer.items.add(file);
        fileInput.files = transfer.files;
        showSelectedFile(file);
    }

    dropZone.addEventListener('click', function () {
        fileInput.click();
    });

    dropZone.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            fileInput.click();
        }
    });

    fileInput.addEventListener('change', function () {
        if (fileInput.files && fileInput.files[0]) {
            showSelectedFile(fileInput.files[0]);
        }
    });

    ['dragenter', 'dragover'].forEach(function (eventName) {
        dropZone.addEventListener(eventName, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('border-blue-400', 'bg-blue-50');
        });
    });

    ['dragleave', 'drop'].forEach(function (eventName) {
        dropZone.addEventListener(eventName, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('border-blue-400', 'bg-blue-50');
        });
    });

    dropZone.addEventListener('drop', function (e) {
        const files = e.dataTransfer && e.dataTransfer.files;
        if (files && files.length > 0) {
            assignFile(files[0]);
        }
    });
});
</script>
@endsection
