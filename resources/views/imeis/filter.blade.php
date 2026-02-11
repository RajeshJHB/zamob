@extends('layouts.app')

@section('title', 'Find IMEI\'s - Filter')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h1 class="text-2xl font-bold mb-4">Filter – Select columns to display</h1>

        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        <form method="GET" action="{{ route('imeis.index') }}" id="imei-filter-form">
            <div class="space-y-6">
                {{-- Search text --}}
                <div class="pb-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold mb-3">Search</h2>
                    <p class="text-sm text-gray-600 mb-2">Show only rows where this text appears in any column (leave blank to skip).</p>
                    <input type="text" name="search" id="search" value="{{ isset($oldSearch) ? e($oldSearch) : '' }}" placeholder="Search in all columns..." class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-md">
                </div>

                {{-- Date filter --}}
                <div class="pb-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold mb-3">Date filter</h2>
                    <div class="space-y-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="date_scope" value="all" {{ (isset($oldDateScope) ? $oldDateScope : 'all') === 'all' ? 'checked' : '' }} class="date-scope-radio">
                            <span class="font-medium">All dates</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="date_scope" value="range" {{ (isset($oldDateScope) && $oldDateScope === 'range') ? 'checked' : '' }} class="date-scope-radio">
                            <span class="font-medium">Between start and end dates</span>
                        </label>
                    </div>
                    <div id="date-range-group" class="mt-4 pl-6 border-l-2 border-gray-200 border-dashed opacity-70" aria-hidden="true">
                        <p class="text-sm text-gray-600 mb-3">Choose the date column and start/end dates.</p>
                        <div class="flex flex-wrap items-end gap-4">
                            <div>
                                <label for="date_column" class="block text-sm font-medium text-gray-700 mb-1">Date column</label>
                                <select name="date_column" id="date_column" class="border border-gray-300 rounded px-3 py-2 shadow-sm date-range-input">
                                    @foreach($dateColumns as $value => $label)
                                        <option value="{{ $value }}" {{ (isset($oldDateColumn) ? $oldDateColumn : 'date_in') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start date</label>
                                <input type="date" name="start_date" id="start_date" value="{{ $oldStartDate ?? '' }}" class="border border-gray-300 rounded px-3 py-2 shadow-sm date-range-input">
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End date</label>
                                <input type="date" name="end_date" id="end_date" value="{{ $oldEndDate ?? '' }}" class="border border-gray-300 rounded px-3 py-2 shadow-sm date-range-input">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Column selection --}}
                <div>
                    <h2 class="text-lg font-semibold mb-3">Columns to display</h2>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="scope" value="all" {{ (isset($oldScope) ? $oldScope : 'all') === 'all' ? 'checked' : '' }} class="imei-scope-radio">
                            <span class="font-medium">All columns</span>
                        </label>
                    </div>
                    <div class="mt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="scope" value="selected" {{ (isset($oldScope) && $oldScope === 'selected') ? 'checked' : '' }} class="imei-scope-radio">
                            <span class="font-medium">Select columns only</span>
                        </label>
                    </div>

                    <div id="columns-group" class="pl-6 mt-2 border-l-2 border-gray-200 border-dashed opacity-70" aria-hidden="true">
                        <p class="text-sm text-gray-600 mb-2">Choose which columns to show:</p>
                        @php $oldColumns = $oldColumns ?? []; @endphp
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($columns as $key => $label)
                                <label class="flex items-center gap-2 cursor-pointer text-sm">
                                    <input type="checkbox" name="columns[]" value="{{ $key }}" class="imei-column-cb" {{ in_array($key, $oldColumns) ? 'checked' : '' }}>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    OK
                </button>
                <a href="{{ route('dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const scopeAll = document.querySelector('input[name="scope"][value="all"]');
    const scopeSelected = document.querySelector('input[name="scope"][value="selected"]');
    const columnsGroup = document.getElementById('columns-group');
    const checkboxes = document.querySelectorAll('.imei-column-cb');

    const dateScopeAll = document.querySelector('input[name="date_scope"][value="all"]');
    const dateScopeRange = document.querySelector('input[name="date_scope"][value="range"]');
    const dateRangeGroup = document.getElementById('date-range-group');
    const dateRangeInputs = document.querySelectorAll('.date-range-input');

    function updateColumnsUi() {
        const selected = scopeSelected && scopeSelected.checked;
        columnsGroup.classList.toggle('opacity-70', !selected);
        columnsGroup.setAttribute('aria-hidden', !selected);
        checkboxes.forEach(function(cb) { cb.disabled = !selected; });
    }

    function updateDateRangeUi() {
        const rangeSelected = dateScopeRange && dateScopeRange.checked;
        dateRangeGroup.classList.toggle('opacity-70', !rangeSelected);
        dateRangeGroup.setAttribute('aria-hidden', !rangeSelected);
        dateRangeInputs.forEach(function(input) { input.disabled = !rangeSelected; });
    }

    document.querySelectorAll('.imei-scope-radio').forEach(function(radio) {
        radio.addEventListener('change', updateColumnsUi);
    });
    document.querySelectorAll('.date-scope-radio').forEach(function(radio) {
        radio.addEventListener('change', updateDateRangeUi);
    });
    updateColumnsUi();
    updateDateRangeUi();

    document.getElementById('imei-filter-form').addEventListener('submit', function(e) {
        if (scopeAll && scopeAll.checked) {
            document.querySelectorAll('.imei-column-cb').forEach(function(cb) { cb.removeAttribute('name'); });
        }
        if (dateScopeAll && dateScopeAll.checked) {
            document.querySelectorAll('.date-range-input').forEach(function(input) { input.removeAttribute('name'); });
        }
    });
});
</script>
@endsection
