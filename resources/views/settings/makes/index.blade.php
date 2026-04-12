@extends('layouts.app')

@section('title', 'Make')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Make</h1>
        <div class="flex gap-2">
            <a href="{{ route('settings.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-1 px-3 rounded text-sm">
                Settings
            </a>
            <a href="{{ route('dashboard') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-sm flex items-center gap-2 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Home
            </a>
        </div>
    </div>

    @if (session('message'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-8 bg-gray-50 p-4 rounded-lg">
        <h2 class="text-xl font-semibold mb-4">Add make</h2>
        <form method="POST" action="{{ route('settings.makes.store') }}" class="flex items-end gap-4">
            @csrf
            <div class="flex-1">
                <label for="new-make-value" class="block text-gray-700 text-sm font-bold mb-2">Make</label>
                <input type="text" name="make" id="new-make-value" value="{{ old('make') }}" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       placeholder="e.g. Apple">
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add
            </button>
        </form>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-4">All makes (A–Z)</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 border-b text-left">Make</th>
                        <th class="px-4 py-2 border-b text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($makes as $make)
                        <tr id="make-row-{{ $make->id }}">
                            <td class="px-4 py-2 border-b">
                                <span class="make-name-display-{{ $make->id }}">{{ $make->make }}</span>
                                <div class="make-name-edit-{{ $make->id }}" style="display: none;">
                                    <form method="POST" action="{{ route('settings.makes.update', $make) }}" id="edit-form-{{ $make->id }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="make" value="{{ old('make', $make->make) }}" required
                                               class="shadow appearance-none border rounded py-1 px-2 text-gray-700 w-full max-w-md">
                                    </form>
                                </div>
                            </td>
                            <td class="px-4 py-2 border-b">
                                <div class="make-actions-view-{{ $make->id }} flex gap-2">
                                    <button type="button" onclick="editMake({{ $make->id }})"
                                            class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                        Edit
                                    </button>
                                    <form method="POST" action="{{ route('settings.makes.destroy', $make) }}" class="inline"
                                          onsubmit="return confirm('Delete this make? IMEI records that use it may be affected.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                                <div class="make-actions-edit-{{ $make->id }} flex gap-2" style="display: none;">
                                    <button type="button" onclick="saveMake({{ $make->id }})"
                                            class="text-green-500 hover:text-green-700 text-sm font-medium">
                                        Save
                                    </button>
                                    <button type="button" onclick="cancelEditMake({{ $make->id }})"
                                            class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                                        Cancel
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-2 text-center text-gray-500">No makes yet. Add one above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const originalMakeNames = {};

    document.addEventListener('DOMContentLoaded', function () {
        @foreach($makes as $make)
            originalMakeNames[{{ $make->id }}] = @json($make->make);
        @endforeach
    });

    function editMake(id) {
        const input = document.querySelector(`.make-name-edit-${id} input[name="make"]`);
        originalMakeNames[id] = input.value;

        document.querySelector(`.make-name-display-${id}`).style.display = 'none';
        document.querySelector(`.make-name-edit-${id}`).style.display = 'block';
        document.querySelector(`.make-actions-view-${id}`).style.display = 'none';
        document.querySelector(`.make-actions-edit-${id}`).style.display = 'flex';
        input.focus();
    }

    function cancelEditMake(id) {
        const input = document.querySelector(`.make-name-edit-${id} input[name="make"]`);
        input.value = originalMakeNames[id];

        document.querySelector(`.make-name-display-${id}`).style.display = 'inline';
        document.querySelector(`.make-name-edit-${id}`).style.display = 'none';
        document.querySelector(`.make-actions-view-${id}`).style.display = 'flex';
        document.querySelector(`.make-actions-edit-${id}`).style.display = 'none';
    }

    function saveMake(id) {
        document.getElementById(`edit-form-${id}`).submit();
    }
</script>
@endsection
