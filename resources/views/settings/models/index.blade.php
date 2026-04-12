@extends('layouts.app')

@section('title', 'Models')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Models</h1>
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

    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
        <h2 class="text-xl font-semibold mb-3">Make</h2>
        <p class="text-gray-600 text-sm mb-3">Choose a make to list and manage models (exact match on stored make text).</p>
        <form method="GET" action="{{ route('settings.models.index') }}" class="max-w-xl">
            <label for="make-select" class="block text-gray-700 text-sm font-bold mb-2">Select make</label>
            <select name="make" id="make-select" onchange="this.form.submit()"
                    class="shadow border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline bg-white">
                <option value="">— Select make —</option>
                @foreach($makes as $makeOption)
                    <option value="{{ $makeOption->make }}" @selected($selectedMake === $makeOption->make)>
                        {{ $makeOption->make }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if($selectedMake !== null)
        <div class="mb-8 bg-gray-50 p-4 rounded-lg">
            <h2 class="text-xl font-semibold mb-4">Add model for <span class="text-gray-800">{{ $selectedMake }}</span></h2>
            <form method="POST" action="{{ route('settings.models.store') }}" class="flex flex-col sm:flex-row sm:items-end gap-4">
                @csrf
                <input type="hidden" name="make" value="{{ $selectedMake }}">
                <div class="flex-1 min-w-0">
                    <label for="new-model-name" class="block text-gray-700 text-sm font-bold mb-2">Model</label>
                    <input type="text" name="model" id="new-model-name" value="{{ old('make') === $selectedMake ? old('model') : '' }}" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           placeholder="e.g. iPhone 15">
                </div>
                <div class="flex-1 min-w-0">
                    <label for="new-model-serial" class="block text-gray-700 text-sm font-bold mb-2">Serial</label>
                    <input type="text" name="serial" id="new-model-serial" value="{{ old('make') === $selectedMake ? old('serial') : '' }}"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                           placeholder="Optional">
                </div>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shrink-0">
                    Add model
                </button>
            </form>
        </div>

        <div>
            <h2 class="text-xl font-semibold mb-4">Models (A–Z) for {{ $selectedMake }}</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-2 border-b text-left">Model</th>
                            <th class="px-4 py-2 border-b text-left">Serial</th>
                            <th class="px-4 py-2 border-b text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($models as $record)
                            <tr id="model-row-{{ $record->id }}">
                                <td class="px-4 py-2 border-b align-top">
                                    <span class="model-cell-display-{{ $record->id }}">{{ $record->model }}</span>
                                    <div class="model-cell-edit-{{ $record->id }}" style="display: none;">
                                        <input type="text" name="model" form="edit-form-{{ $record->id }}" value="{{ old('model', $record->model) }}" required
                                               class="shadow appearance-none border rounded py-1 px-2 text-gray-700 w-full max-w-md">
                                    </div>
                                </td>
                                <td class="px-4 py-2 border-b align-top">
                                    <span class="serial-cell-display-{{ $record->id }}">{{ $record->serial }}</span>
                                    <div class="serial-cell-edit-{{ $record->id }}" style="display: none;">
                                        <input type="text" name="serial" form="edit-form-{{ $record->id }}" value="{{ old('serial', $record->serial) }}"
                                               class="shadow appearance-none border rounded py-1 px-2 text-gray-700 w-full max-w-md">
                                    </div>
                                </td>
                                <td class="px-4 py-2 border-b align-top">
                                    <form method="POST" action="{{ route('settings.models.update', $record) }}" id="edit-form-{{ $record->id }}" class="hidden">
                                        @csrf
                                        @method('PUT')
                                    </form>
                                    <div class="model-actions-view-{{ $record->id }} flex gap-2">
                                        <button type="button" onclick="editModelRow({{ $record->id }})"
                                                class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('settings.models.destroy', $record) }}" class="inline"
                                              onsubmit="return confirm('Delete this model?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                    <div class="model-actions-edit-{{ $record->id }} flex gap-2" style="display: none;">
                                        <button type="button" onclick="saveModelRow({{ $record->id }})"
                                                class="text-green-500 hover:text-green-700 text-sm font-medium">
                                            Save
                                        </button>
                                        <button type="button" onclick="cancelEditModelRow({{ $record->id }})"
                                                class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                                            Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-center text-gray-500">No models for this make yet. Add one above.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @elseif($makes->isEmpty())
        <p class="text-gray-600 text-sm">Add makes under <a href="{{ route('settings.makes.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">Make</a> first, then return here.</p>
    @endif
</div>

<script>
    const originalModelRows = {};

    document.addEventListener('DOMContentLoaded', function () {
        @foreach($models as $record)
            originalModelRows[{{ $record->id }}] = {
                model: @json($record->model),
                serial: @json($record->serial),
            };
        @endforeach
    });

    function editModelRow(id) {
        const modelInput = document.querySelector(`[form="edit-form-${id}"][name="model"]`);
        const serialInput = document.querySelector(`[form="edit-form-${id}"][name="serial"]`);
        originalModelRows[id] = { model: modelInput.value, serial: serialInput.value };

        document.querySelector(`.model-cell-display-${id}`).style.display = 'none';
        document.querySelector(`.serial-cell-display-${id}`).style.display = 'none';
        document.querySelector(`.model-cell-edit-${id}`).style.display = 'block';
        document.querySelector(`.serial-cell-edit-${id}`).style.display = 'block';
        document.querySelector(`.model-actions-view-${id}`).style.display = 'none';
        document.querySelector(`.model-actions-edit-${id}`).style.display = 'flex';
        modelInput.focus();
    }

    function cancelEditModelRow(id) {
        const modelInput = document.querySelector(`[form="edit-form-${id}"][name="model"]`);
        const serialInput = document.querySelector(`[form="edit-form-${id}"][name="serial"]`);
        modelInput.value = originalModelRows[id].model;
        serialInput.value = originalModelRows[id].serial;

        document.querySelector(`.model-cell-display-${id}`).style.display = 'inline';
        document.querySelector(`.serial-cell-display-${id}`).style.display = 'inline';
        document.querySelector(`.model-cell-edit-${id}`).style.display = 'none';
        document.querySelector(`.serial-cell-edit-${id}`).style.display = 'none';
        document.querySelector(`.model-actions-view-${id}`).style.display = 'flex';
        document.querySelector(`.model-actions-edit-${id}`).style.display = 'none';
    }

    function saveModelRow(id) {
        document.getElementById(`edit-form-${id}`).submit();
    }
</script>
@endsection
