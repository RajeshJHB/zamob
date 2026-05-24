@extends('layouts.app')

@section('title', 'Note types')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Note types</h1>
        <div class="flex gap-2">
            <a href="{{ route('settings.notes.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-1 px-3 rounded text-sm">
                Note Settings
            </a>
        </div>
    </div>

    @if (session('message'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-8 bg-gray-50 p-4 rounded-lg">
        <h2 class="text-xl font-semibold mb-4">Add note type</h2>
        <form method="POST" action="{{ route('settings.note-types.store') }}" class="flex items-end gap-4">
            @csrf
            <div class="flex-1">
                <label for="new-note-type-name" class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                <input type="text" name="name" id="new-note-type-name" value="{{ old('name') }}" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       placeholder="e.g. Warranty">
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add
            </button>
        </form>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-4">All note types</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 border-b text-left">Name</th>
                        <th class="px-4 py-2 border-b text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($noteTypes as $row)
                        <tr id="note-type-row-{{ $row->id }}">
                            <td class="px-4 py-2 border-b">
                                <span class="note-type-display-{{ $row->id }}">{{ $row->name }}</span>
                                <div class="note-type-edit-{{ $row->id }}" style="display: none;">
                                    <form method="POST" action="{{ route('settings.note-types.update', $row) }}" id="edit-note-type-form-{{ $row->id }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" value="{{ old('name', $row->name) }}" required
                                               class="shadow appearance-none border rounded py-1 px-2 text-gray-700 w-full max-w-md">
                                    </form>
                                </div>
                            </td>
                            <td class="px-4 py-2 border-b">
                                <div class="note-type-actions-view-{{ $row->id }} flex gap-2">
                                    <button type="button" onclick="editNoteType({{ $row->id }})"
                                            class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                        Edit
                                    </button>
                                    @if(auth()->user()->canDeleteImeiReferenceData())
                                        <form method="POST" action="{{ route('settings.note-types.destroy', $row) }}" class="inline"
                                              onsubmit="return confirm('Delete this note type?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <div class="note-type-actions-edit-{{ $row->id }} flex gap-2" style="display: none;">
                                    <button type="button" onclick="saveNoteType({{ $row->id }})"
                                            class="text-green-500 hover:text-green-700 text-sm font-medium">
                                        Save
                                    </button>
                                    <button type="button" onclick="cancelEditNoteType({{ $row->id }})"
                                            class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                                        Cancel
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-2 text-center text-gray-500">No note types yet. Add one above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const originalNoteTypeNames = {};

    document.addEventListener('DOMContentLoaded', function () {
        @foreach($noteTypes as $row)
            originalNoteTypeNames[{{ $row->id }}] = @json($row->name);
        @endforeach
    });

    function editNoteType(id) {
        const input = document.querySelector(`.note-type-edit-${id} input[name="name"]`);
        originalNoteTypeNames[id] = input.value;

        document.querySelector(`.note-type-display-${id}`).style.display = 'none';
        document.querySelector(`.note-type-edit-${id}`).style.display = 'block';
        document.querySelector(`.note-type-actions-view-${id}`).style.display = 'none';
        document.querySelector(`.note-type-actions-edit-${id}`).style.display = 'flex';
        input.focus();
    }

    function cancelEditNoteType(id) {
        const input = document.querySelector(`.note-type-edit-${id} input[name="name"]`);
        input.value = originalNoteTypeNames[id];

        document.querySelector(`.note-type-display-${id}`).style.display = 'inline';
        document.querySelector(`.note-type-edit-${id}`).style.display = 'none';
        document.querySelector(`.note-type-actions-view-${id}`).style.display = 'flex';
        document.querySelector(`.note-type-actions-edit-${id}`).style.display = 'none';
    }

    function saveNoteType(id) {
        document.getElementById(`edit-note-type-form-${id}`).submit();
    }
</script>
@endsection
