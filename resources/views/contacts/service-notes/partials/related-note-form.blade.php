@php
    $selectedId = (int) old('primary_service_note_id', $selectedRelatedNote?->id ?? 0);
@endphp

<div>
    <label for="primary_service_note_id" class="block text-sm font-medium text-gray-700 mb-1">
        Related service note (optional)
    </label>
    <select
        name="primary_service_note_id"
        id="primary_service_note_id"
        class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full bg-white"
    >
        <option value="" @selected($selectedId === 0)>— None —</option>
        @foreach($relatedNotesForSelect ?? [] as $relatedOption)
            <option value="{{ $relatedOption->id }}" @selected($selectedId === (int) $relatedOption->id)>
                {{ $relatedOption->formattedNoteNumber() }} — {{ $relatedOption->heading }}
            </option>
        @endforeach
    </select>
    @error('primary_service_note_id')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
