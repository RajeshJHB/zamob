@extends('layouts.app')

@section('title', 'Locations')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Locations</h1>
        <div class="flex gap-2">
            <a href="{{ route('settings.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-1 px-3 rounded text-sm">
                IMEI Settings
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
        <h2 class="text-xl font-semibold mb-4">Add location</h2>
        <form method="POST" action="{{ route('settings.locations.store') }}" class="flex items-end gap-4">
            @csrf
            <div class="flex-1">
                <label for="new-location-value" class="block text-gray-700 text-sm font-bold mb-2">Location</label>
                <input type="text" name="location" id="new-location-value" value="{{ old('location') }}" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       placeholder="e.g. Warehouse A">
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add
            </button>
        </form>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-4">All locations (A–Z)</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 border-b text-left">Location</th>
                        <th class="px-4 py-2 border-b text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locations as $row)
                        <tr id="location-row-{{ $row->id }}">
                            <td class="px-4 py-2 border-b">
                                <span class="location-display-{{ $row->id }}">{{ $row->location }}</span>
                                <div class="location-edit-{{ $row->id }}" style="display: none;">
                                    <form method="POST" action="{{ route('settings.locations.update', $row) }}" id="edit-form-{{ $row->id }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="location" value="{{ old('location', $row->location) }}" required
                                               class="shadow appearance-none border rounded py-1 px-2 text-gray-700 w-full max-w-md">
                                    </form>
                                </div>
                            </td>
                            <td class="px-4 py-2 border-b">
                                <div class="location-actions-view-{{ $row->id }} flex gap-2">
                                    <button type="button" onclick="editLocation({{ $row->id }})"
                                            class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                        Edit
                                    </button>
                                    @if(auth()->user()->canDeleteImeiReferenceData())
                                        <form method="POST" action="{{ route('settings.locations.destroy', $row) }}" class="inline"
                                              onsubmit="return confirm('Delete this location? IMEI records that use it may be affected.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <div class="location-actions-edit-{{ $row->id }} flex gap-2" style="display: none;">
                                    <button type="button" onclick="saveLocation({{ $row->id }})"
                                            class="text-green-500 hover:text-green-700 text-sm font-medium">
                                        Save
                                    </button>
                                    <button type="button" onclick="cancelEditLocation({{ $row->id }})"
                                            class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                                        Cancel
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-2 text-center text-gray-500">No locations yet. Add one above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const originalLocations = {};

    document.addEventListener('DOMContentLoaded', function () {
        @foreach($locations as $row)
            originalLocations[{{ $row->id }}] = @json($row->location);
        @endforeach
    });

    function editLocation(id) {
        const input = document.querySelector(`.location-edit-${id} input[name="location"]`);
        originalLocations[id] = input.value;

        document.querySelector(`.location-display-${id}`).style.display = 'none';
        document.querySelector(`.location-edit-${id}`).style.display = 'block';
        document.querySelector(`.location-actions-view-${id}`).style.display = 'none';
        document.querySelector(`.location-actions-edit-${id}`).style.display = 'flex';
        input.focus();
    }

    function cancelEditLocation(id) {
        const input = document.querySelector(`.location-edit-${id} input[name="location"]`);
        input.value = originalLocations[id];

        document.querySelector(`.location-display-${id}`).style.display = 'inline';
        document.querySelector(`.location-edit-${id}`).style.display = 'none';
        document.querySelector(`.location-actions-view-${id}`).style.display = 'flex';
        document.querySelector(`.location-actions-edit-${id}`).style.display = 'none';
    }

    function saveLocation(id) {
        document.getElementById(`edit-form-${id}`).submit();
    }
</script>
@endsection
