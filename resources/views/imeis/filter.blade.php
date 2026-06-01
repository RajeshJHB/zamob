@extends('layouts.app')

@section('title', 'IMEI - Filter')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h1 class="text-2xl font-bold mb-4">
            Filter –
            @if(!empty($currentProfileName))
                "{{ $currentProfileName }}"
            @else
                Select columns to display
            @endif
        </h1>

        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        <form method="GET" action="{{ route('imeis.index') }}" id="imei-filter-form">
            <input type="hidden" name="from_filter" value="1">
            <div class="space-y-6">
                {{-- Search text --}}
                <div class="pb-4 border-b border-gray-200 space-y-3">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-1 min-w-0">
                            <h2 class="text-lg font-semibold">Search</h2>
                            <p class="text-sm text-gray-600">Use one or both fields. A row must match <strong>both</strong> texts (each can appear in any column).</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 shrink-0">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Search
                            </button>
                            <button type="button" class="imei-filter-clear-button bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">
                                Clear
                            </button>
                            <a href="{{ route('dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded inline-block text-center">
                                Cancel
                            </a>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">First text (optional)</label>
                            <input
                                type="text"
                                name="search"
                                id="search"
                                value="{{ isset($oldSearch) ? e($oldSearch) : '' }}"
                                placeholder="Search text 1 – any column"
                                class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-md"
                            >
                        </div>
                        <div>
                            <label for="search2" class="block text-sm font-medium text-gray-700 mb-1">Second text (optional)</label>
                            <input
                                type="text"
                                name="search2"
                                id="search2"
                                value="{{ isset($oldSearch2) ? e($oldSearch2) : '' }}"
                                placeholder="Search text 2 – must also appear"
                                class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-md"
                            >
                        </div>
                    </div>
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
                                <input
                                    type="date"
                                    name="start_date"
                                    id="start_date"
                                    value="{{ $oldStartDate ?? '' }}"
                                    @if(! empty($oldEndDate)) max="{{ $oldEndDate }}" @endif
                                    class="border border-gray-300 rounded px-3 py-2 shadow-sm date-range-input"
                                >
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End date</label>
                                <input
                                    type="date"
                                    name="end_date"
                                    id="end_date"
                                    value="{{ $oldEndDate ?? '' }}"
                                    @if(! empty($oldStartDate)) min="{{ $oldStartDate }}" @endif
                                    class="border border-gray-300 rounded px-3 py-2 shadow-sm date-range-input"
                                >
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

                {{-- Field filters (Location / Make / Type / Status / Sale Type) --}}
                <div class="pb-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold mb-3">Filter by field</h2>
                    <p class="text-sm text-gray-600 mb-4">
                        Choose up to three filters. Values come from <strong>IMEI Settings</strong>.
                        Tick <strong>Exclude (not equal)</strong> to match records that do <strong>not</strong> have the selected value.
                        Leave the field as <strong>No filter</strong> to ignore a row.
                    </p>
                    <div class="space-y-4">
                        @foreach($fieldFilterRows as $fieldFilterRow)
                            @php
                                $filterIndex = $fieldFilterRow['index'];
                                $oldFieldFilter = $fieldFilterRow['field'];
                                $oldFieldValue = $fieldFilterRow['value'];
                                $fieldValueOptions = $oldFieldFilter !== ''
                                    ? ($fieldFilterPicklists[$oldFieldFilter] ?? [])
                                    : [];
                            @endphp
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end border border-gray-100 rounded-lg p-3 bg-gray-50/50">
                                <div>
                                    <label for="field_filter_{{ $filterIndex }}" class="block text-xs font-medium text-gray-700 mb-1">
                                        Filter {{ $filterIndex }} — field
                                    </label>
                                    <select
                                        name="field_filter_{{ $filterIndex }}"
                                        id="field_filter_{{ $filterIndex }}"
                                        class="imei-field-filter-field border border-gray-300 rounded px-2 py-1 shadow-sm w-full bg-white"
                                        data-filter-index="{{ $filterIndex }}"
                                    >
                                        <option value="">No filter</option>
                                        @foreach(\App\Support\ImeiFieldFilter::FIELD_LABELS as $fieldKey => $fieldLabel)
                                            <option
                                                value="{{ $fieldKey }}"
                                                @selected($oldFieldFilter === $fieldKey)
                                            >{{ $fieldLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 flex items-center gap-2 text-xs text-gray-700">
                                        <input
                                            type="checkbox"
                                            name="field_not_{{ $filterIndex }}"
                                            id="field_not_{{ $filterIndex }}"
                                            value="1"
                                            @checked($fieldFilterRow['not'])
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        >
                                        <span>Exclude (not equal to value below)</span>
                                    </label>
                                    <label for="field_value_{{ $filterIndex }}" class="block text-xs font-medium text-gray-700 mb-1">
                                        Filter {{ $filterIndex }} — value
                                    </label>
                                    <select
                                        name="{{ $oldFieldFilter !== '' ? 'field_value_'.$filterIndex : '' }}"
                                        id="field_value_{{ $filterIndex }}"
                                        class="imei-field-filter-value border border-gray-300 rounded px-2 py-1 shadow-sm w-full bg-white"
                                        data-filter-index="{{ $filterIndex }}"
                                        data-initial-value="{{ e($oldFieldValue) }}"
                                        @disabled($oldFieldFilter === '')
                                    >
                                        <option value="">{{ $oldFieldFilter !== '' ? '(choose value)' : '(select a field first)' }}</option>
                                        @if($oldFieldValue !== '' && ! in_array($oldFieldValue, $fieldValueOptions, true))
                                            <option value="{{ $oldFieldValue }}" selected>{{ $oldFieldValue }}</option>
                                        @endif
                                        @foreach($fieldValueOptions as $fieldValueOption)
                                            <option value="{{ $fieldValueOption }}" @selected($oldFieldValue === $fieldValueOption)>{{ $fieldValueOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Sort order --}}
                <div>
                    <h2 class="text-lg font-semibold mb-3">Sort order</h2>
                    <p class="text-sm text-gray-600 mb-3">
                        Choose up to two of the displayed columns to sort by (primary then secondary),
                        each ascending or descending. Leave blank to use the default sort (newest Date In first).
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-2">Primary sort</h3>
                            <label class="block text-xs font-medium text-gray-700 mb-1" for="sort1_column">Column</label>
                            <select name="sort1_column" id="sort1_column" class="border border-gray-300 rounded px-2 py-1 shadow-sm w-full mb-2">
                                <option value="">(none)</option>
                                @foreach($columns as $key => $label)
                                    <option value="{{ $key }}" {{ (isset($oldSort1Column) && $oldSort1Column === $key) ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <label class="block text-xs font-medium text-gray-700 mb-1" for="sort1_dir">Direction</label>
                            @php $oldSort1Dir = $oldSort1Dir ?? 'asc'; @endphp
                            <select name="sort1_dir" id="sort1_dir" class="border border-gray-300 rounded px-2 py-1 shadow-sm w-full">
                                <option value="asc" {{ $oldSort1Dir === 'asc' ? 'selected' : '' }}>Ascending</option>
                                <option value="desc" {{ $oldSort1Dir === 'desc' ? 'selected' : '' }}>Descending</option>
                            </select>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800 mb-2">Secondary sort</h3>
                            <label class="block text-xs font-medium text-gray-700 mb-1" for="sort2_column">Column</label>
                            <select name="sort2_column" id="sort2_column" class="border border-gray-300 rounded px-2 py-1 shadow-sm w-full mb-2">
                                <option value="">(none)</option>
                                @foreach($columns as $key => $label)
                                    <option value="{{ $key }}" {{ (isset($oldSort2Column) && $oldSort2Column === $key) ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <label class="block text-xs font-medium text-gray-700 mb-1" for="sort2_dir">Direction</label>
                            @php $oldSort2Dir = $oldSort2Dir ?? 'asc'; @endphp
                            <select name="sort2_dir" id="sort2_dir" class="border border-gray-300 rounded px-2 py-1 shadow-sm w-full">
                                <option value="asc" {{ $oldSort2Dir === 'asc' ? 'selected' : '' }}>Ascending</option>
                                <option value="desc" {{ $oldSort2Dir === 'desc' ? 'selected' : '' }}>Descending</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Saved filter profiles --}}
                <div>
                    <h2 class="text-lg font-semibold mb-3">Saved filter profiles</h2>
                    @if(session('message'))
                        <div class="mb-3 bg-green-100 border border-green-400 text-green-700 px-3 py-2 rounded text-sm">
                            {{ session('message') }}
                        </div>
                    @endif
                    @php
                        $selectedProfileId = (string) request('profile_id', $activeProfileId ?? '');
                        $selectedProfile = $selectedProfileId !== ''
                            ? $savedFilters->firstWhere('id', (int) $selectedProfileId)
                            : null;
                    @endphp

                    <div class="space-y-3">
                        <div>
                            <label for="profile_name" class="block text-xs font-medium text-gray-700 mb-1">
                                Profile name
                            </label>
                            <input
                                type="text"
                                name="profile_name"
                                id="profile_name"
                                class="border border-gray-300 rounded px-3 py-1.5 shadow-sm w-full text-sm"
                                placeholder="Enter or choose a profile name"
                                value="{{ $currentProfileName ?? '' }}"
                            >
                        </div>

                        <div class="space-y-3">
                            <div>
                                <label for="profile_select" class="block text-xs font-medium text-gray-700 mb-1">
                                    Profile
                                </label>
                                <select
                                    id="profile_select"
                                    name="profile_id"
                                    class="border border-gray-300 rounded px-2 py-1.5 shadow-sm w-full text-sm"
                                >
                                    <option value="">(none)</option>
                                    @foreach($savedFilters as $filter)
                                        <option
                                            value="{{ $filter->id }}"
                                            data-is-default="{{ $filter->is_default ? '1' : '0' }}"
                                            @selected($selectedProfileId === (string) $filter->id)
                                        >{{ $filter->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <label class="flex items-center gap-2 cursor-pointer @if($selectedProfile === null) opacity-60 @endif">
                                <input
                                    type="checkbox"
                                    id="profile_is_default"
                                    value="1"
                                    @checked($selectedProfile?->is_default ?? false)
                                    @disabled($selectedProfile === null)
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:cursor-not-allowed"
                                >
                                <span class="text-sm text-gray-800">Default profile</span>
                            </label>
                            <p class="text-xs text-gray-500">
                                Only profiles with <strong>selected columns only</strong> (no search, dates, sorts, or field filters) can be default.
                            </p>

                            <div class="flex flex-wrap gap-2">
                                <button type="button"
                                        id="load-profile-button"
                                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-1.5 px-3 rounded text-xs">
                                    Load profile
                                </button>
                                <button type="button"
                                        id="save-profile-button"
                                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1.5 px-3 rounded text-xs">
                                    Save profile
                                </button>
                                <button type="button"
                                        id="delete-profile-button"
                                        class="bg-red-500 hover:bg-red-700 text-white font-bold py-1.5 px-3 rounded text-xs">
                                    Delete profile
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500">
                            Saving with an existing profile name will overwrite it (you will be asked to confirm).
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Search
                </button>
                <button type="button" class="imei-filter-clear-button bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">
                    Clear
                </button>
                <a href="{{ route('dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded inline-block text-center">
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

    const filterForm = document.getElementById('imei-filter-form');
    const saveProfileButton = document.getElementById('save-profile-button');
    const loadProfileButton = document.getElementById('load-profile-button');
    const deleteProfileButton = document.getElementById('delete-profile-button');
    const profileSelect = document.getElementById('profile_select');
    const profileIsDefault = document.getElementById('profile_is_default');
    const defaultProfilePostUrl = @json(route('imeis.filter.default'));
    const defaultProfileCsrf = @json(csrf_token());
    const profileNameInput = document.getElementById('profile_name');
    const existingProfileNames = @json($savedFilters->pluck('name')->values());
    const fieldFilterPicklists = @json($fieldFilterPicklists);
    const fieldFilterInitialValues = {
        1: @json($oldFieldValue1 ?? ''),
        2: @json($oldFieldValue2 ?? ''),
    };

    function populateFieldFilterValueSelect(index, preserveValue) {
        const fieldSelect = document.getElementById('field_filter_' + index);
        const valueSelect = document.getElementById('field_value_' + index);
        if (!fieldSelect || !valueSelect) {
            return;
        }

        const field = fieldSelect.value;
        const initialValue = preserveValue !== undefined
            ? preserveValue
            : (valueSelect.value
                || valueSelect.getAttribute('data-initial-value')
                || fieldFilterInitialValues[index]
                || '');

        valueSelect.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = field ? '(choose value)' : '(select a field first)';
        valueSelect.appendChild(placeholder);

        if (!field) {
            valueSelect.disabled = true;
            valueSelect.removeAttribute('name');

            return;
        }

        valueSelect.disabled = false;
        valueSelect.setAttribute('name', 'field_value_' + index);

        const options = fieldFilterPicklists[field] || [];
        let matchedInitial = false;

        if (initialValue !== '' && !options.includes(initialValue)) {
            const orphan = document.createElement('option');
            orphan.value = initialValue;
            orphan.textContent = initialValue;
            orphan.selected = true;
            valueSelect.appendChild(orphan);
            matchedInitial = true;
        }

        options.forEach(function (label) {
            const option = document.createElement('option');
            option.value = label;
            option.textContent = label;
            if (!matchedInitial && label === initialValue) {
                option.selected = true;
                matchedInitial = true;
            }
            valueSelect.appendChild(option);
        });
    }

    function initFieldFilters() {
        document.querySelectorAll('.imei-field-filter-field').forEach(function (fieldSelect) {
            const index = fieldSelect.getAttribute('data-filter-index');
            const valueSelect = document.getElementById('field_value_' + index);

            fieldSelect.addEventListener('change', function () {
                populateFieldFilterValueSelect(index, '');
            });

            if (!valueSelect || valueSelect.options.length <= 1) {
                populateFieldFilterValueSelect(index);
            } else if (fieldSelect.value === '') {
                valueSelect.disabled = true;
                valueSelect.removeAttribute('name');
            } else {
                valueSelect.disabled = false;
                valueSelect.setAttribute('name', 'field_value_' + index);
            }
        });
    }

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
        syncImeiDateRange();
    }

    function syncImeiDateRange() {
        const startInput = document.getElementById('start_date');
        const endInput = document.getElementById('end_date');

        if (! startInput || ! endInput) {
            return;
        }

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

    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', syncImeiDateRange);
        endDateInput.addEventListener('change', syncImeiDateRange);
        syncImeiDateRange();
    }

    document.querySelectorAll('.imei-scope-radio').forEach(function(radio) {
        radio.addEventListener('change', updateColumnsUi);
    });
    document.querySelectorAll('.date-scope-radio').forEach(function(radio) {
        radio.addEventListener('change', updateDateRangeUi);
    });
    updateColumnsUi();
    updateDateRangeUi();
    initFieldFilters();

    document.getElementById('imei-filter-form').addEventListener('submit', function(e) {
        if (scopeAll && scopeAll.checked) {
            document.querySelectorAll('.imei-column-cb').forEach(function(cb) { cb.removeAttribute('name'); });
        }
        if (dateScopeAll && dateScopeAll.checked) {
            document.querySelectorAll('.date-range-input').forEach(function(input) { input.removeAttribute('name'); });
        }
        [1, 2, 3].forEach(function (index) {
            const fieldSelect = document.getElementById('field_filter_' + index);
            const valueSelect = document.getElementById('field_value_' + index);
            const notCheckbox = document.getElementById('field_not_' + index);
            if (fieldSelect && fieldSelect.value === '') {
                fieldSelect.removeAttribute('name');
                if (valueSelect) {
                    valueSelect.removeAttribute('name');
                }
                if (notCheckbox) {
                    notCheckbox.removeAttribute('name');
                }
            } else if (valueSelect && valueSelect.value === '') {
                valueSelect.removeAttribute('name');
            }
        });
    });

    if (saveProfileButton && filterForm) {
        saveProfileButton.addEventListener('click', function () {
            const nameInput = document.getElementById('profile_name');
            const name = nameInput ? nameInput.value.trim() : '';
            if (!name) {
                alert('Please enter a profile name.');
                if (nameInput) nameInput.focus();
                return;
            }

            if (existingProfileNames.includes(name)) {
                if (!confirm('A profile with this name already exists. Overwrite it?')) {
                    return;
                }
            }

            const postForm = document.createElement('form');
            postForm.method = 'POST';
            postForm.action = '{{ route('imeis.filter.save') }}';

            // CSRF
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = '{{ csrf_token() }}';
            postForm.appendChild(tokenInput);

            const formData = new FormData(filterForm);
            formData.append('profile_name', name);

            formData.forEach(function (value, key) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                postForm.appendChild(input);
            });

            document.body.appendChild(postForm);
            postForm.submit();
        });
    }

    if (loadProfileButton && profileSelect) {
        loadProfileButton.addEventListener('click', function () {
            const id = profileSelect.value;
            if (!id) {
                window.location.href = '{{ route('imeis.filter.clear') }}';

                return;
            }
            const url = '{{ url('/imeis/filter/apply') }}' + '/' + encodeURIComponent(id);
            window.location.href = url;
        });
    }

    function syncDefaultCheckboxFromSelect() {
        if (!profileSelect || !profileIsDefault) {
            return;
        }

        const selected = profileSelect.options[profileSelect.selectedIndex];
        const hasProfile = Boolean(selected && selected.value);

        profileIsDefault.disabled = !hasProfile;
        profileIsDefault.checked = hasProfile && selected.getAttribute('data-is-default') === '1';
        profileIsDefault.closest('label')?.classList.toggle('opacity-60', !hasProfile);
    }

    function submitDefaultProfileForm(isDefault) {
        if (!profileSelect) {
            return;
        }

        const profileId = profileSelect.value;
        if (!profileId && isDefault) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = defaultProfilePostUrl;

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = defaultProfileCsrf;
        form.appendChild(tokenInput);

        const profileInput = document.createElement('input');
        profileInput.type = 'hidden';
        profileInput.name = 'profile_id';
        profileInput.value = profileId;
        form.appendChild(profileInput);

        const flagInput = document.createElement('input');
        flagInput.type = 'hidden';
        flagInput.name = 'is_default';
        flagInput.value = isDefault ? '1' : '0';
        form.appendChild(flagInput);

        document.body.appendChild(form);
        form.submit();
    }

    if (profileSelect && profileNameInput) {
        profileSelect.addEventListener('change', function () {
            const selected = profileSelect.options[profileSelect.selectedIndex];
            if (selected && selected.value) {
                profileNameInput.value = selected.textContent.trim();
            }
            syncDefaultCheckboxFromSelect();
        });
        syncDefaultCheckboxFromSelect();
    }

    if (profileIsDefault) {
        profileIsDefault.addEventListener('change', function () {
            if (!profileSelect || !profileSelect.value) {
                profileIsDefault.checked = false;

                return;
            }
            submitDefaultProfileForm(profileIsDefault.checked);
        });
    }

    if (deleteProfileButton && profileSelect) {
        deleteProfileButton.addEventListener('click', function () {
            const id = profileSelect.value;
            if (!id) {
                alert('Please select a profile to delete.');
                return;
            }
            if (!confirm('Delete this filter profile?')) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ url('/imeis/filter') }}' + '/' + encodeURIComponent(id);

            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = '{{ csrf_token() }}';
            form.appendChild(tokenInput);

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);

            document.body.appendChild(form);
            form.submit();
        });
    }

    document.querySelectorAll('.imei-filter-clear-button').forEach(function (clearButton) {
        clearButton.addEventListener('click', function () {
            const search1 = document.getElementById('search');
            const search2 = document.getElementById('search2');
            if (search1) search1.value = '';
            if (search2) search2.value = '';

            if (dateScopeAll) {
                dateScopeAll.checked = true;
                dateScopeAll.dispatchEvent(new Event('change', { bubbles: true }));
            }

            const dateColumn = document.getElementById('date_column');
            if (dateColumn) {
                dateColumn.value = 'date_in';
            }

            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            if (startDate) startDate.value = '';
            if (endDate) endDate.value = '';
            syncImeiDateRange();

            ['sort1_column', 'sort2_column'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            ['sort1_dir', 'sort2_dir'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.value = 'asc';
            });

            [1, 2, 3].forEach(function (index) {
                const fieldSelect = document.getElementById('field_filter_' + index);
                const valueSelect = document.getElementById('field_value_' + index);
                const notCheckbox = document.getElementById('field_not_' + index);

                if (fieldSelect) {
                    fieldSelect.value = '';
                    fieldSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (valueSelect) {
                    valueSelect.value = '';
                }

                if (notCheckbox) {
                    notCheckbox.checked = false;
                }
            });

            if (profileSelect) {
                profileSelect.value = '';
                syncDefaultCheckboxFromSelect();
            }
            if (profileNameInput) {
                profileNameInput.value = '';
            }
        });
    });
});
</script>
@endsection
