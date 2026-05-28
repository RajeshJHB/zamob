@extends('layouts.app')

@section('title', 'Notes')

@section('content')
@php
    use Illuminate\Support\Str;
@endphp
<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <h1 class="text-2xl font-bold">Notes</h1>
            <a
                href="{{ route('notes.print', array_merge(request()->query(), ['auto' => 1])) }}"
                target="_blank"
                rel="noopener noreferrer"
                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded text-sm"
            >
                Print
            </a>
        </div>

        <form method="GET" action="{{ route('notes.index') }}" class="flex flex-wrap items-end gap-3 mb-6 pb-6 border-b border-gray-200">
            <div class="min-w-[12rem]">
                <label for="note_status" class="block text-sm font-medium text-gray-700 mb-1">Note status</label>
                <select name="note_status" id="note_status" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-xs">
                    <option value="{{ \App\Models\ServiceNote::STATUS_OPEN }}" @selected(($noteStatus ?? \App\Models\ServiceNote::STATUS_OPEN) === \App\Models\ServiceNote::STATUS_OPEN)>Open</option>
                    <option value="{{ \App\Models\ServiceNote::STATUS_CLOSED }}" @selected(($noteStatus ?? '') === \App\Models\ServiceNote::STATUS_CLOSED)>Closed</option>
                    <option value="" @selected(($noteStatus ?? \App\Models\ServiceNote::STATUS_OPEN) === '')>All statuses</option>
                </select>
                <label class="mt-2 flex items-center gap-2 cursor-pointer">
                    <input
                        type="checkbox"
                        name="only_mine"
                        value="1"
                        class="rounded border-gray-300"
                        @checked($onlyMine ?? false)
                    >
                    <span class="text-sm text-gray-800">Only my notes</span>
                </label>
            </div>
            <div class="min-w-[14rem]">
                <span class="block text-sm font-medium text-gray-700 mb-1">Note type</span>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="radio"
                            name="note_type_scope"
                            value="all"
                            class="notes-type-scope-radio"
                            @checked(($noteTypeScope ?? 'all') === 'all')
                        >
                        <span class="text-sm text-gray-800">All note types</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="radio"
                            name="note_type_scope"
                            value="selected"
                            class="notes-type-scope-radio"
                            @checked(($noteTypeScope ?? 'all') === 'selected')
                        >
                        <span class="text-sm text-gray-800">Selected types only</span>
                    </label>
                </div>
                <div
                    id="notes-type-checkboxes"
                    class="mt-2 pl-4 border-l-2 border-gray-200 space-y-1.5 @if(($noteTypeScope ?? 'all') === 'all') hidden @endif"
                >
                    @foreach($noteTypes as $type)
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-800">
                            <input
                                type="checkbox"
                                value="{{ $type->id }}"
                                class="notes-type-checkbox rounded border-gray-300"
                                @checked(in_array($type->id, $noteTypeIds ?? [], true))
                                @if(($noteTypeScope ?? 'all') === 'selected') name="note_type_id[]" @endif
                            >
                            <span>{{ $type->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('note_type_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="notes_start_date" class="block text-sm font-medium text-gray-700 mb-1">Start date</label>
                <input
                    type="date"
                    name="start_date"
                    id="notes_start_date"
                    value="{{ $startDate ?? '' }}"
                    @if(! empty($endDate)) max="{{ $endDate }}" @endif
                    class="border border-gray-300 rounded px-3 py-2 shadow-sm @error('start_date') border-red-500 @enderror"
                >
                @error('start_date')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="notes_end_date" class="block text-sm font-medium text-gray-700 mb-1">End date</label>
                <input
                    type="date"
                    name="end_date"
                    id="notes_end_date"
                    value="{{ $endDate ?? '' }}"
                    @if(! empty($startDate)) min="{{ $startDate }}" @endif
                    class="border border-gray-300 rounded px-3 py-2 shadow-sm @error('end_date') border-red-500 @enderror"
                >
                @error('end_date')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex-1 min-w-[12rem]">
                <label for="note_q" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="note_q" id="note_q" value="{{ $term }}" placeholder="Contact name, note text, SN-…" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-md">
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Search notes</button>
        </form>

        @if($listingAll ?? false)
            <p class="text-sm text-gray-600 mb-4">Showing all service notes (newest first, {{ \App\Support\ServiceNoteBrowsePageSize::SIZE }} per page).</p>
        @elseif($listingOpenDefault ?? false)
            <p class="text-sm text-gray-600 mb-4">Showing open service notes (newest first, {{ \App\Support\ServiceNoteBrowsePageSize::SIZE }} per page).</p>
        @else
            <p class="text-sm text-gray-600 mb-4">
                @if(($noteStatusFilter ?? null) === \App\Models\ServiceNote::STATUS_CLOSED)
                    Note status: <strong>Closed</strong>
                @elseif(($noteStatusFilter ?? null) === \App\Models\ServiceNote::STATUS_OPEN)
                    Note status: <strong>Open</strong>
                @elseif(($noteStatusFilter ?? null) === null && ! ($onlyMine ?? false))
                    Note status: <strong>All statuses</strong>
                @endif
                @if(($noteStatusFilter ?? null) !== null && ($onlyMine ?? false))
                    <span class="text-gray-400">&middot;</span>
                @endif
                @if($onlyMine ?? false)
                    <strong>Only my notes</strong>
                @endif
                @if((($noteStatusFilter ?? null) !== null || ($onlyMine ?? false)) && ($selectedNoteTypes ?? collect())->isNotEmpty())
                    <span class="text-gray-400">&middot;</span>
                @endif
                @if(($selectedNoteTypes ?? collect())->isNotEmpty())
                    Note {{ ($selectedNoteTypes ?? collect())->count() === 1 ? 'type' : 'types' }}:
                    <strong>{{ ($selectedNoteTypes ?? collect())->pluck('name')->join(', ') }}</strong>
                @endif
                @if(($selectedNoteTypes ?? collect())->isNotEmpty() || ($noteStatusFilter ?? null) !== null || ($onlyMine ?? false))
                    @if(($startDate ?? null) || ($endDate ?? null))
                        <span class="text-gray-400">&middot;</span>
                    @endif
                @endif
                @if(($startDate ?? null) || ($endDate ?? null))
                    @if(($startDate ?? null) && ($endDate ?? null))
                        Dates: <strong>{{ $startDate }} – {{ $endDate }}</strong>
                    @elseif($startDate ?? null)
                        From date: <strong>{{ $startDate }}</strong>
                    @else
                        Until date: <strong>{{ $endDate }}</strong>
                    @endif
                @endif
                @if(($selectedNoteTypes ?? collect())->isNotEmpty() || ($noteStatusFilter ?? null) !== null || ($startDate ?? null) || ($endDate ?? null))
                    @if($term !== '')
                        <span class="text-gray-400">&middot;</span>
                    @endif
                @endif
                @if($term !== '')
                    Search: <strong>{{ $term }}</strong>
                @endif
            </p>
        @endif

        @forelse($notes as $note)
            <div class="border border-gray-200 rounded-lg p-4 mb-4 hover:bg-gray-50">
                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 mb-1 text-sm">
                    <div class="flex flex-wrap items-center gap-2 text-gray-600">
                        @if($note->noteType)
                            <span class="font-medium text-gray-800">{{ $note->noteType->name }}</span>
                            <span class="text-gray-400">&middot;</span>
                        @endif
                        <span class="font-mono font-semibold text-gray-800">{{ $note->formattedNoteNumber() }}</span>
                    </div>
                    @include('contacts.service-notes.partials.times', ['note' => $note, 'class' => 'sm:justify-end'])
                </div>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">{{ $note->heading }}</h2>
                <p class="text-sm text-gray-700 whitespace-pre-wrap mb-3">{{ Str::limit($note->body, 300) }}</p>
                <p class="text-sm">
                    <span class="text-gray-500">Contact:</span>
                    <a href="{{ route('contacts.show', ['contact' => $note->contact, 'note' => $note->id]) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                        {{ $note->contact->displayName() }}
                    </a>
                </p>
            </div>
        @empty
            <p class="text-gray-600">
                @if($listingAll ?? false)
                    No service notes yet.
                @else
                    No service notes matched your search.
                @endif
            </p>
        @endforelse

        @if($notes->hasPages())
            <div class="mt-4">
                {{ $notes->links() }}
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const scopeRadios = document.querySelectorAll('.notes-type-scope-radio');
    const typeCheckboxesPanel = document.getElementById('notes-type-checkboxes');
    const typeCheckboxes = document.querySelectorAll('.notes-type-checkbox');

    function updateNoteTypeUi() {
        const selectedScope = document.querySelector('.notes-type-scope-radio[value="selected"]');
        const showCheckboxes = selectedScope && selectedScope.checked;

        if (typeCheckboxesPanel) {
            typeCheckboxesPanel.classList.toggle('hidden', ! showCheckboxes);
        }

        typeCheckboxes.forEach(function (checkbox) {
            if (showCheckboxes) {
                checkbox.disabled = false;
                checkbox.setAttribute('name', 'note_type_id[]');
            } else {
                checkbox.checked = false;
                checkbox.disabled = true;
                checkbox.removeAttribute('name');
            }
        });
    }

    scopeRadios.forEach(function (radio) {
        radio.addEventListener('change', updateNoteTypeUi);
    });
    updateNoteTypeUi();

    const startInput = document.getElementById('notes_start_date');
    const endInput = document.getElementById('notes_end_date');

    if (! startInput || ! endInput) {
        return;
    }

    function syncNotesDateRange() {
        if (startInput.value) {
            endInput.min = startInput.value;
            if (endInput.value && endInput.value < startInput.value) {
                endInput.value = startInput.value;
            }
        } else {
            endInput.removeAttribute('min');
        }

        if (endInput.value) {
            startInput.max = endInput.value;
            if (startInput.value && startInput.value > endInput.value) {
                startInput.value = endInput.value;
            }
        } else {
            startInput.removeAttribute('max');
        }
    }

    startInput.addEventListener('change', syncNotesDateRange);
    endInput.addEventListener('change', syncNotesDateRange);
    syncNotesDateRange();
});
</script>
@endsection
