@extends('layouts.app')

@section('title', 'Default Settings')

@section('content')
@php
    $readOnly = ! ($canEditDefaultSettings ?? false);
@endphp
<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-lg shadow-md p-4 border border-gray-200">
        <form id="default-settings-form" method="POST" action="{{ route('settings.default.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="flex items-center justify-between gap-3">
                <h1 class="text-lg font-bold text-gray-900">Default Settings</h1>
                @if(! $readOnly)
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1.5 px-3 rounded text-sm shrink-0">
                        Save defaults
                    </button>
                @endif
            </div>

            @if($readOnly)
                <p class="text-amber-800 bg-amber-50 border border-amber-200 rounded px-2.5 py-1.5 text-xs">
                    Read-only. Only Role 4 users can change these settings.
                </p>
            @endif

            @if (session('message'))
                <div id="default-settings-saved-message" class="bg-green-100 border border-green-400 text-green-800 px-3 py-2 rounded text-sm">
                    {{ session('message') }}
                </div>
            @endif

            <section class="space-y-2">
                <h2 class="text-base font-semibold text-gray-900">Browse lists</h2>
                <p class="text-gray-600 text-xs mb-1">
                    Limit for blank <strong>IMEI</strong> or <strong>Contacts</strong> browse (newest records only).
                </p>

                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                    <label for="browse_list_limit" class="shrink-0 font-medium text-gray-700">Max records</label>
                    <input
                        type="number"
                        name="browse_list_limit"
                        id="browse_list_limit"
                        value="{{ old('browse_list_limit', $browseListLimit) }}"
                        min="{{ \App\Support\BrowseListLimit::MIN_LIMIT }}"
                        max="{{ \App\Support\BrowseListLimit::MAX_LIMIT }}"
                        step="1"
                        @required(! $readOnly)
                        @disabled($readOnly)
                        class="border border-gray-300 rounded px-2 py-1 shadow-sm w-20 text-sm tabular-nums @error('browse_list_limit') border-red-500 @enderror disabled:bg-gray-100 disabled:text-gray-600"
                    >
                    <span class="text-gray-500">
                        Default {{ \App\Support\BrowseListLimit::DEFAULT_LIMIT }},
                        {{ \App\Support\BrowseListLimit::MIN_LIMIT }}–{{ \App\Support\BrowseListLimit::MAX_LIMIT }}
                    </span>
                    @error('browse_list_limit')
                        <p class="w-full text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <section class="space-y-2 border-t border-gray-200 pt-4">
                <h2 class="text-base font-semibold text-gray-900">In Shop row colours</h2>
                <p class="text-gray-600 text-xs mb-2">
                    Colour <strong>In Shop</strong> rows on IMEI and Home by days since Date in.
                </p>

                <div class="space-y-1.5 text-xs">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <label for="age_band_1_days" class="w-[4.5rem] shrink-0 font-medium text-gray-700">1st x Days</label>
                        <input
                            type="number"
                            name="age_band_1_days"
                            id="age_band_1_days"
                            value="{{ old('age_band_1_days', $ageSettings->band1Days) }}"
                            min="1"
                            step="1"
                            @required(! $readOnly)
                            @disabled($readOnly)
                            class="js-age-band-days border border-gray-300 rounded px-2 py-1 shadow-sm w-14 text-sm @error('age_band_1_days') border-red-500 @enderror disabled:bg-gray-100 disabled:text-gray-600"
                        >
                        @include('settings.default.partials.age-colour-select', ['name' => 'tier1_colour', 'selected' => old('tier1_colour', $ageSettings->tier1Colour), 'readOnly' => $readOnly])
                        <span id="js-age-band-range-1" class="text-gray-500 tabular-nums">{{ $ageSettings->rangeLabelForTier(1) }}</span>
                        @error('age_band_1_days')
                            <p class="w-full text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <label for="age_band_2_days" class="w-[4.5rem] shrink-0 font-medium text-gray-700">Next x Days</label>
                        <input
                            type="number"
                            name="age_band_2_days"
                            id="age_band_2_days"
                            value="{{ old('age_band_2_days', $ageSettings->band2Days) }}"
                            min="1"
                            step="1"
                            @required(! $readOnly)
                            @disabled($readOnly)
                            class="js-age-band-days border border-gray-300 rounded px-2 py-1 shadow-sm w-14 text-sm @error('age_band_2_days') border-red-500 @enderror disabled:bg-gray-100 disabled:text-gray-600"
                        >
                        @include('settings.default.partials.age-colour-select', ['name' => 'tier2_colour', 'selected' => old('tier2_colour', $ageSettings->tier2Colour), 'readOnly' => $readOnly])
                        <span id="js-age-band-range-2" class="text-gray-500 tabular-nums">{{ $ageSettings->rangeLabelForTier(2) }}</span>
                        @error('age_band_2_days')
                            <p class="w-full text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <label for="age_band_3_days" class="w-[4.5rem] shrink-0 font-medium text-gray-700">Next x Days</label>
                        <input
                            type="number"
                            name="age_band_3_days"
                            id="age_band_3_days"
                            value="{{ old('age_band_3_days', $ageSettings->band3Days) }}"
                            min="1"
                            step="1"
                            @required(! $readOnly)
                            @disabled($readOnly)
                            class="js-age-band-days border border-gray-300 rounded px-2 py-1 shadow-sm w-14 text-sm @error('age_band_3_days') border-red-500 @enderror disabled:bg-gray-100 disabled:text-gray-600"
                        >
                        @include('settings.default.partials.age-colour-select', ['name' => 'tier3_colour', 'selected' => old('tier3_colour', $ageSettings->tier3Colour), 'readOnly' => $readOnly])
                        <span id="js-age-band-range-3" class="text-gray-500 tabular-nums">{{ $ageSettings->rangeLabelForTier(3) }}</span>
                        @error('age_band_3_days')
                            <p class="w-full text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <span class="w-[4.5rem] shrink-0" aria-hidden="true"></span>
                        <span class="w-14 shrink-0" aria-hidden="true"></span>
                        @include('settings.default.partials.age-colour-select', ['name' => 'tier4_colour', 'selected' => old('tier4_colour', $ageSettings->tier4Colour), 'readOnly' => $readOnly])
                        <span id="js-age-band-range-4" class="text-gray-500 tabular-nums">{{ $ageSettings->rangeLabelForTier(4) }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 border-t border-gray-200 pt-2 mt-2">
                        <span class="w-[4.5rem] shrink-0 font-medium text-gray-700">No Date in</span>
                        <span class="w-14 shrink-0" aria-hidden="true"></span>
                        @include('settings.default.partials.age-colour-select', ['name' => 'missing_date_colour', 'selected' => old('missing_date_colour', $ageSettings->missingDateColour), 'readOnly' => $readOnly])
                    </div>
                </div>
            </section>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const settingsForm = document.getElementById('default-settings-form');
    const savedMessage = document.getElementById('default-settings-saved-message');

    function dismissSavedMessage() {
        if (savedMessage) {
            savedMessage.remove();
        }
    }

    if (settingsForm && savedMessage) {
        settingsForm.addEventListener('input', dismissSavedMessage);
        settingsForm.addEventListener('change', dismissSavedMessage);
    }

    const swatchBase = 'js-age-colour-swatch inline-block h-5 w-5 rounded border border-gray-300 shrink-0 ';

    function syncAgeColourSwatch(select) {
        const wrap = select.closest('.age-colour-select-wrap');
        if (!wrap) {
            return;
        }

        const swatch = wrap.querySelector('.js-age-colour-swatch');
        const option = select.options[select.selectedIndex];
        const swatchClass = option ? (option.getAttribute('data-swatch') || 'bg-gray-200') : 'bg-gray-200';

        if (swatch) {
            swatch.className = swatchBase + swatchClass;
        }
    }

    document.querySelectorAll('.js-age-colour-select').forEach(function (select) {
        select.addEventListener('change', function () {
            syncAgeColourSwatch(select);
        });
    });

    function bandDayValue(id) {
        const input = document.getElementById(id);
        const value = input ? parseInt(input.value, 10) : NaN;

        return Number.isFinite(value) && value > 0 ? value : 0;
    }

    function updateAgeBandRangeLabels() {
        const band1 = bandDayValue('age_band_1_days');
        const band2 = bandDayValue('age_band_2_days');
        const band3 = bandDayValue('age_band_3_days');
        const end1 = band1;
        const end2 = band1 + band2;
        const end3 = band1 + band2 + band3;

        const range1 = document.getElementById('js-age-band-range-1');
        const range2 = document.getElementById('js-age-band-range-2');
        const range3 = document.getElementById('js-age-band-range-3');
        const range4 = document.getElementById('js-age-band-range-4');

        if (range1 && band1 > 0) {
            range1.textContent = '0–' + end1;
        }
        if (range2 && band2 > 0) {
            range2.textContent = (end1 + 1) + '–' + end2;
        }
        if (range3 && band3 > 0) {
            range3.textContent = (end2 + 1) + '–' + end3;
        }
        if (range4 && end3 > 0) {
            range4.textContent = 'Over ' + end3;
        }
    }

    document.querySelectorAll('.js-age-band-days').forEach(function (input) {
        input.addEventListener('input', updateAgeBandRangeLabels);
    });

    updateAgeBandRangeLabels();
});
</script>
@endsection
