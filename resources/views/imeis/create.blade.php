@extends('layouts.app')

@php
    /** @var array<string, mixed>|null $viewRecord */
    $viewRecord = $viewRecord ?? null;
    $formUnlocked = $errors->any() || ! empty($viewRecord);
    $readonlyAfterSave = ! empty($viewRecord) && ! $errors->any();

    $updateRouteId = null;
    if ($errors->any() && old('imei_record_id')) {
        $updateRouteId = old('imei_record_id');
    } elseif (! empty($viewRecord['id'])) {
        $updateRouteId = $viewRecord['id'];
    }

    $formAction = $updateRouteId !== null
        ? route('imeis.update', ['imei' => $updateRouteId])
        : route('imeis.store');

    $prefillRecordId = $updateRouteId;
    $roAttr = $readonlyAfterSave ? 'readonly' : '';
    $roFieldClass = $readonlyAfterSave ? 'bg-gray-50 cursor-default' : '';

    $imeiFieldValue = function (string $key) use ($errors, $viewRecord): string {
        if ($errors->any()) {
            return old($key, '');
        }
        if (! empty($viewRecord)) {
            if (! array_key_exists($key, $viewRecord)) {
                return '';
            }
            $val = $viewRecord[$key];
            if ($key === 'date_in' && $val !== null) {
                return substr((string) $val, 0, 16);
            }
            if ($key === 'notes') {
                return $val === null ? '' : (string) $val;
            }
            if ($key === 'selling_price') {
                return $val === null || $val === '' ? '' : (string) $val;
            }

            return $val === null ? '' : (string) $val;
        }

        return old($key, '');
    };

    $viewListHref = '#';
    if ($readonlyAfterSave && ! empty($viewRecord['imei'])) {
        $viewListHref = route('imeis.index').'?search='.urlencode((string) $viewRecord['imei']).'&scope=all&date_scope=all';
    }

    /** @var \Illuminate\Support\Collection<int, \App\Models\ImeiType>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\ImeiType> $imeiTypes */
    $imeiTypes = $imeiTypes ?? collect();
    $currentTypeForSelect = $imeiFieldValue('type');
    $typeInReferenceTable = $imeiTypes->contains(fn (\App\Models\ImeiType $t): bool => $t->type === $currentTypeForSelect);

    /** @var \Illuminate\Support\Collection<int, \App\Models\ImeiStatus>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\ImeiStatus> $imeiStatuses */
    $imeiStatuses = $imeiStatuses ?? collect();
    $currentStatusForSelect = $imeiFieldValue('status');
    $statusInReferenceTable = $imeiStatuses->contains(fn (\App\Models\ImeiStatus $s): bool => $s->status === $currentStatusForSelect);

    /** @var \Illuminate\Support\Collection<int, \App\Models\ImeiLocation>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\ImeiLocation> $imeiLocations */
    $imeiLocations = $imeiLocations ?? collect();
    $currentLocationForSelect = $imeiFieldValue('location');
    $locationInReferenceTable = $imeiLocations->contains(fn (\App\Models\ImeiLocation $loc): bool => $loc->location === $currentLocationForSelect);

    /** @var \Illuminate\Support\Collection<int, \App\Models\ImeiMake>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\ImeiMake> $imeiMakes */
    $imeiMakes = $imeiMakes ?? collect();
    $currentMakeForSelect = $imeiFieldValue('make');
    $makeInReferenceTable = $imeiMakes->contains(fn (\App\Models\ImeiMake $m): bool => $m->make === $currentMakeForSelect);

    /** @var \Illuminate\Support\Collection<int, \App\Models\ImeiModel>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\ImeiModel> $allImeiModels */
    $allImeiModels = $allImeiModels ?? collect();
    $currentModelForSelect = $imeiFieldValue('model');

    $modelsForSelectedMake = $makeInReferenceTable
        ? $allImeiModels->where('make', $currentMakeForSelect)->sortBy(fn (\App\Models\ImeiModel $m): string => $m->model.(string) ($m->serial ?? ''))->unique('model')->values()
        : collect();

    $modelInReferenceTableForMake = $allImeiModels->contains(
        fn (\App\Models\ImeiModel $m): bool => $m->make === $currentMakeForSelect && $m->model === $currentModelForSelect
    );

    $showLegacyModelOption = $currentModelForSelect !== '' && ! $modelInReferenceTableForMake;

    $imeiModelsCatalogForScript = $allImeiModels->map(fn (\App\Models\ImeiModel $m): array => [
        'make' => $m->make,
        'model' => $m->model,
        'serial' => (string) ($m->serial ?? ''),
    ])->values()->all();
@endphp

@section('title', $createPageHeading ?: 'Add IMEI')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-2">
            <h1 class="text-2xl font-bold">{{ $createPageHeading ?: 'Add IMEI' }}</h1>
            <div id="imei-form-actions-top-wrap" class="flex flex-wrap items-center justify-between gap-3 flex-1 min-w-0 shrink @unless($formUnlocked) hidden @endunless">
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="submit" form="imei-create-form" id="imei-submit-btn-top" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900" @if($readonlyAfterSave) hidden @endif>
                        Save IMEI
                    </button>
                    <button type="button" id="imei-cancel-new-btn-top" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" @if($readonlyAfterSave) hidden @endif>
                        Cancel
                    </button>
                    <button type="button" id="imei-edit-btn-top" class="inline-flex items-center px-4 py-2 border border-gray-900 text-sm font-medium rounded-md text-gray-900 bg-white hover:bg-gray-50" @unless($readonlyAfterSave) hidden @endunless>
                        Edit
                    </button>
                    <button type="button" id="imei-cancel-edit-btn-top" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" @unless($readonlyAfterSave) hidden @endunless>
                        Exit
                    </button>
                </div>
                @if($updateRouteId !== null && auth()->user()->canDeleteImeiReferenceData())
                    <div class="imei-delete-action-wrap flex items-center shrink-0" @if($readonlyAfterSave && !$errors->any()) hidden @endif>
                        <button type="submit" form="imei-delete-form" id="imei-delete-btn-top" class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                onclick="return confirm('Are you sure you want to delete this record? This cannot be undone.');">
                            Delete
                        </button>
                    </div>
                @endif
            </div>
        </div>
        <p class="text-sm text-gray-600 mb-6">
            {{ $createPageIntro ?? 'Enter the IMEI and choose ADD. Leave the option below unchecked for standard 15-digit IMEIs. If the number is new, fill in the details; if it already exists, review the record and use Edit to update it.' }}
        </p>

        @if (session('message'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('message') }}
            </div>
        @endif

        @if($updateRouteId !== null && auth()->user()->canDeleteImeiReferenceData())
            <form id="imei-delete-form" method="POST" action="{{ route('imeis.destroy', ['imei' => $updateRouteId]) }}" class="hidden" aria-hidden="true">
                @csrf
                @method('DELETE')
            </form>
        @endif

        <form method="POST" action="{{ $formAction }}" class="space-y-6" id="imei-create-form">
            @csrf
            <input type="hidden" name="imei_non_standard" id="imei_non_standard" value="{{ old('imei_non_standard', $defaultImeiNonStandard ?? '0') }}">
            <input type="hidden" name="imei_record_id" id="imei_record_id" value="{{ $updateRouteId ?? '' }}">
            <input type="hidden" name="_method" id="imei_method_spoof" value="PUT" @if($updateRouteId === null) disabled @endif>

            {{-- Step 1: IMEI only (probe is not submitted) --}}
            <div id="imei-step-check" class="space-y-4 pb-4 border-b border-gray-200 @if($formUnlocked) hidden @endif">
                <div class="max-w-md">
                    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 mb-2">
                        <label for="imei_probe" class="text-sm font-medium text-gray-700">
                            IMEI <span class="text-red-600">*</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-800 cursor-pointer shrink-0">
                            <input
                                type="checkbox"
                                id="imei_non_std_toggle"
                                class="border-gray-300 rounded text-gray-900 focus:ring-gray-900"
                                @checked(old('imei_non_standard', $defaultImeiNonStandard ?? '0') === '1')
                            >
                            <span class="font-medium">Non-standard IMEI</span>
                        </label>
                    </div>
                    <input
                        type="text"
                        id="imei_probe"
                        autocomplete="off"
                        maxlength="255"
                        class="imei-probe-input border rounded px-3 py-2 shadow-sm w-full bg-white border-gray-300 transition-colors duration-150"
                    >
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="button" id="imei-check-btn" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900">
                        ADD
                    </button>
                </div>
                <div id="imei-lookup-feedback" class="text-sm min-h-[1.25rem]" role="status" aria-live="polite"></div>
            </div>

            {{-- Step 2: full form --}}
            <div id="imei-step-rest" class="space-y-6 @unless($formUnlocked) hidden @endunless">
                <div id="imei-saved-banner" class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 @unless($readonlyAfterSave) hidden @endunless">
                    <p>Details below are read-only. Use <strong>Edit</strong> if you need to change anything else.</p>
                </div>
                <div id="imei-nonstandard-new-banner" class="hidden rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                    <p class="font-medium">Non-standard IMEI</p>
                    <p class="mt-1 text-blue-800">This value is not validated as a standard 15-digit IMEI (alphanumeric and symbols allowed; spaces, dashes, and slashes were removed for matching). Fill in the details below and save when ready.</p>
                </div>
                <div id="imei-existing-banner" class="hidden rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <p class="font-medium">This IMEI is already in the database.</p>
                    <p class="mt-1 text-amber-800">Details are shown below in read-only mode. Use <strong>Edit</strong> to change them.</p>
                    <p class="mt-2">
                        <a id="imei-view-list-link" href="{{ $viewListHref }}" class="underline font-medium text-amber-900">View this IMEI in the results list</a>
                    </p>
                </div>

                <div class="pb-4 border-b border-gray-200 space-y-2">
                    <h2 class="text-lg font-semibold">IMEI</h2>
                    <div class="max-w-md">
                        <label for="imei_final" class="block text-sm font-medium text-gray-700 mb-1">IMEI</label>
                        <input
                            type="text"
                            name="imei"
                            id="imei_final"
                            value="{{ $imeiFieldValue('imei') }}"
                            readonly
                            required
                            class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full bg-gray-50 text-gray-900 @error('imei') border-red-500 @enderror"
                        >
                        @error('imei')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-4 pb-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold">Identifiers</h2>
                    <div>
                        <label for="sn" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['sn'] ?? 'SN' }}</label>
                        <input type="text" name="sn" id="sn" value="{{ $imeiFieldValue('sn') }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-md {{ $roFieldClass }} @error('sn') border-red-500 @enderror">
                        @error('sn')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="item_code" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['item_code'] ?? 'Item code' }}</label>
                        <input type="text" name="item_code" id="item_code" value="{{ $imeiFieldValue('item_code') }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-md {{ $roFieldClass }} @error('item_code') border-red-500 @enderror">
                        @error('item_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-4 pb-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold">Device</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="make" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['make'] ?? 'Make' }}</label>
                            <select name="make" id="make" @disabled($readonlyAfterSave) class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full bg-white {{ $roFieldClass }} @error('make') border-red-500 @enderror">
                                <option value="">— Select make —</option>
                                @if($currentMakeForSelect !== '' && ! $makeInReferenceTable)
                                    <option value="{{ $currentMakeForSelect }}" selected>{{ $currentMakeForSelect }} (not in list)</option>
                                @endif
                                @foreach($imeiMakes as $imeiMake)
                                    <option value="{{ $imeiMake->make }}" @selected($currentMakeForSelect === $imeiMake->make)>{{ $imeiMake->make }}</option>
                                @endforeach
                            </select>
                            @error('make')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="model" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['model'] ?? 'Model' }}</label>
                            <select name="model" id="model" @disabled($readonlyAfterSave) class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full bg-white {{ $roFieldClass }} @error('model') border-red-500 @enderror">
                                <option value="">— Select model —</option>
                                @if($showLegacyModelOption)
                                    <option value="{{ $currentModelForSelect }}" selected>{{ $currentModelForSelect }} (not in list)</option>
                                @endif
                                @foreach($modelsForSelectedMake as $imeiModelRow)
                                    <option value="{{ $imeiModelRow->model }}" @selected(! $showLegacyModelOption && $currentModelForSelect === $imeiModelRow->model)>{{ $imeiModelRow->model }}@if(($imeiModelRow->serial ?? '') !== '') ({{ $imeiModelRow->serial }})@endif</option>
                                @endforeach
                            </select>
                            @error('model')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['type'] ?? 'Type' }}</label>
                            <select name="type" id="type" @disabled($readonlyAfterSave) class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full bg-white {{ $roFieldClass }} @error('type') border-red-500 @enderror">
                                <option value="">— Select type —</option>
                                @if($currentTypeForSelect !== '' && ! $typeInReferenceTable)
                                    <option value="{{ $currentTypeForSelect }}" selected>{{ $currentTypeForSelect }} (not in list)</option>
                                @endif
                                @foreach($imeiTypes as $imeiType)
                                    <option value="{{ $imeiType->type }}" @selected($currentTypeForSelect === $imeiType->type)>{{ $imeiType->type }}</option>
                                @endforeach
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['status'] ?? 'Status' }}</label>
                            <select name="status" id="status" @disabled($readonlyAfterSave) class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full bg-white {{ $roFieldClass }} @error('status') border-red-500 @enderror">
                                <option value="">— Select status —</option>
                                @if($currentStatusForSelect !== '' && ! $statusInReferenceTable)
                                    <option value="{{ $currentStatusForSelect }}" selected>{{ $currentStatusForSelect }} (not in list)</option>
                                @endif
                                @foreach($imeiStatuses as $imeiStatus)
                                    <option value="{{ $imeiStatus->status }}" @selected($currentStatusForSelect === $imeiStatus->status)>{{ $imeiStatus->status }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="space-y-4 pb-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold">Location &amp; dates</h2>
                    <p id="imei-date-hint-new" class="text-xs text-gray-500 @if($readonlyAfterSave) hidden @endif">Leave date in blank to use the current time when saving a new record.</p>
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['location'] ?? 'Location' }}</label>
                        <select name="location" id="location" @disabled($readonlyAfterSave) class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-md bg-white {{ $roFieldClass }} @error('location') border-red-500 @enderror">
                            <option value="">— Select location —</option>
                            @if($currentLocationForSelect !== '' && ! $locationInReferenceTable)
                                <option value="{{ $currentLocationForSelect }}" selected>{{ $currentLocationForSelect }} (not in list)</option>
                            @endif
                            @foreach($imeiLocations as $imeiLocation)
                                <option value="{{ $imeiLocation->location }}" @selected($currentLocationForSelect === $imeiLocation->location)>{{ $imeiLocation->location }}</option>
                            @endforeach
                        </select>
                        @error('location')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="date_in" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['date_in'] ?? 'Date in' }}</label>
                            <input type="datetime-local" name="date_in" id="date_in" value="{{ $imeiFieldValue('date_in') }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('date_in') border-red-500 @enderror">
                            @error('date_in')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="stock_take_date" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['stock_take_date'] ?? 'Stock take date' }}</label>
                            <input type="text" name="stock_take_date" id="stock_take_date" value="{{ $imeiFieldValue('stock_take_date') }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('stock_take_date') border-red-500 @enderror">
                            @error('stock_take_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="space-y-4 pb-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold">People &amp; reference</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="phonenumber" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['phonenumber'] ?? 'Phone' }}</label>
                            <input type="text" name="phonenumber" id="phonenumber" value="{{ $imeiFieldValue('phonenumber') }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('phonenumber') border-red-500 @enderror">
                            @error('phonenumber')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="ref" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['ref'] ?? 'Ref' }}</label>
                            <input type="text" name="ref" id="ref" value="{{ $imeiFieldValue('ref') }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('ref') border-red-500 @enderror">
                            @error('ref')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <label for="staff" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['staff'] ?? 'Staff' }}</label>
                        <input type="text" name="staff" id="staff" value="{{ $imeiFieldValue('staff') }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-md {{ $roFieldClass }} @error('staff') border-red-500 @enderror">
                        @error('staff')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-4 pb-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold">Sales</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="ourON" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['ourON'] ?? 'ourON' }}</label>
                            <input type="text" name="ourON" id="ourON" value="{{ $imeiFieldValue('ourON') }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('ourON') border-red-500 @enderror">
                            @error('ourON')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="salesON" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['salesON'] ?? 'salesON' }}</label>
                            <input type="text" name="salesON" id="salesON" value="{{ $imeiFieldValue('salesON') }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('salesON') border-red-500 @enderror">
                            @error('salesON')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="cost_excl" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['cost_excl'] ?? 'Cost excl' }}</label>
                            <input type="text" name="cost_excl" id="cost_excl" value="{{ $imeiFieldValue('cost_excl') }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('cost_excl') border-red-500 @enderror">
                            @error('cost_excl')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="selling_price" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['selling_price'] ?? 'Selling price' }}</label>
                            <input type="number" name="selling_price" id="selling_price" value="{{ $imeiFieldValue('selling_price') }}" step="1" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('selling_price') border-red-500 @enderror">
                            @error('selling_price')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['notes'] ?? 'Notes' }}</label>
                    <textarea name="notes" id="notes" rows="4" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('notes') border-red-500 @enderror">{{ $imeiFieldValue('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" id="imei-submit-btn" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900" @if($readonlyAfterSave) hidden @endif>
                            Save IMEI
                        </button>
                        <button type="button" id="imei-cancel-new-btn" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" @if($readonlyAfterSave) hidden @endif>
                            Cancel
                        </button>
                        <button type="button" id="imei-edit-btn" class="inline-flex items-center px-4 py-2 border border-gray-900 text-sm font-medium rounded-md text-gray-900 bg-white hover:bg-gray-50" @unless($readonlyAfterSave) hidden @endunless>
                            Edit
                        </button>
                        <button type="button" id="imei-cancel-edit-btn" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" @unless($readonlyAfterSave) hidden @endunless>
                            Exit
                        </button>
                    </div>
                    @if($updateRouteId !== null && auth()->user()->canDeleteImeiReferenceData())
                        <div class="imei-delete-action-wrap flex items-center shrink-0" @if($readonlyAfterSave && !$errors->any()) hidden @endif>
                            <button type="submit" form="imei-delete-form" id="imei-delete-btn-bottom" class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    onclick="return confirm('Are you sure you want to delete this record? This cannot be undone.');">
                                Delete
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const lookupUrl = @json(route('imeis.lookup'));
    const resultsBase = @json(route('imeis.index'));
    const storeUrl = @json(route('imeis.store'));
    const imeisResourceBase = @json(rtrim(url('/imeis'), '/'));
    const formUnlocked = @json($formUnlocked);
    const prefillRecordId = @json($prefillRecordId);
    const readonlyAfterSave = @json($readonlyAfterSave);
    const maxNonStandardImeiLength = @json(\App\Support\ImeiValidator::MAX_NON_STANDARD_IMEI_LENGTH);
    const imeiModelsCatalog = @json($imeiModelsCatalogForScript);

    const form = document.getElementById('imei-create-form');
    const stepCheck = document.getElementById('imei-step-check');
    const stepRest = document.getElementById('imei-step-rest');
    const probe = document.getElementById('imei_probe');
    const checkBtn = document.getElementById('imei-check-btn');
    const feedback = document.getElementById('imei-lookup-feedback');
    const imeiFinal = document.getElementById('imei_final');
    const cancelNewBtn = document.getElementById('imei-cancel-new-btn');
    const cancelNewBtnTop = document.getElementById('imei-cancel-new-btn-top');
    const recordIdInput = document.getElementById('imei_record_id');
    const methodSpoof = document.getElementById('imei_method_spoof');
    const existingBanner = document.getElementById('imei-existing-banner');
    const savedBanner = document.getElementById('imei-saved-banner');
    const viewListLink = document.getElementById('imei-view-list-link');
    const dateHintNew = document.getElementById('imei-date-hint-new');
    const submitBtn = document.getElementById('imei-submit-btn');
    const editBtn = document.getElementById('imei-edit-btn');
    const cancelEditBtn = document.getElementById('imei-cancel-edit-btn');
    const submitBtnTop = document.getElementById('imei-submit-btn-top');
    const editBtnTop = document.getElementById('imei-edit-btn-top');
    const cancelEditBtnTop = document.getElementById('imei-cancel-edit-btn-top');
    const topActionsWrap = document.getElementById('imei-form-actions-top-wrap');
    const imeiNonStandardInput = document.getElementById('imei_non_standard');
    const nonStdToggle = document.getElementById('imei_non_std_toggle');
    const nonStandardNewBanner = document.getElementById('imei-nonstandard-new-banner');
    const deleteActionWraps = document.querySelectorAll('.imei-delete-action-wrap');
    const makeSelect = document.getElementById('make');
    const modelSelect = document.getElementById('model');

    const mutableSelector = '.js-imei-mutable';

    function uniqueModelsForMake(make) {
        const rows = imeiModelsCatalog.filter(function (r) {
            return r.make === make;
        });
        const seen = {};
        const out = [];
        rows.forEach(function (r) {
            if (seen[r.model]) {
                return;
            }
            seen[r.model] = true;
            out.push(r);
        });
        out.sort(function (a, b) {
            const c = String(a.model).localeCompare(String(b.model));
            if (c !== 0) {
                return c;
            }
            return String(a.serial || '').localeCompare(String(b.serial || ''));
        });

        return out;
    }

    function refreshModelSelectForMake(make) {
        if (!modelSelect) {
            return;
        }
        const selected = modelSelect.value;
        modelSelect.innerHTML = '';
        const emptyOpt = document.createElement('option');
        emptyOpt.value = '';
        emptyOpt.textContent = '— Select model —';
        modelSelect.appendChild(emptyOpt);
        uniqueModelsForMake(make).forEach(function (r) {
            const o = document.createElement('option');
            o.value = r.model;
            let label = r.model;
            const serial = String(r.serial || '').trim();
            if (serial !== '') {
                label += ' (' + serial + ')';
            }
            o.textContent = label;
            modelSelect.appendChild(o);
        });
        const optionValues = Array.prototype.map.call(modelSelect.options, function (opt) {
            return opt.value;
        });
        if (selected && optionValues.indexOf(selected) !== -1) {
            modelSelect.value = selected;
        }
    }

    function syncMakeModelFromRecord(record) {
        if (!makeSelect || !modelSelect || !record) {
            return;
        }
        const makeVal = record.make == null ? '' : String(record.make);
        const modelVal = record.model == null ? '' : String(record.model);
        makeSelect.value = makeVal;
        refreshModelSelectForMake(makeVal);
        const inCat = imeiModelsCatalog.some(function (r) {
            return r.make === makeVal && r.model === modelVal;
        });
        if (!inCat && modelVal !== '') {
            const o = document.createElement('option');
            o.value = modelVal;
            o.textContent = modelVal + ' (not in list)';
            modelSelect.appendChild(o);
        }
        modelSelect.value = modelVal;
    }

    function onMakeChanged() {
        if (!makeSelect || !modelSelect) {
            return;
        }
        const makeVal = makeSelect.value;
        const prevModel = modelSelect.value;
        refreshModelSelectForMake(makeVal);
        if (makeVal && prevModel) {
            const stillExists = imeiModelsCatalog.some(function (r) {
                return r.make === makeVal && r.model === prevModel;
            });
            if (stillExists) {
                modelSelect.value = prevModel;
            }
        }
    }

    function setImeiDeleteActionsVisible(visible) {
        deleteActionWraps.forEach(function (wrap) {
            if (visible) {
                wrap.removeAttribute('hidden');
            } else {
                wrap.setAttribute('hidden', '');
            }
        });
    }

    function isNonStandardToggleOn() {
        return nonStdToggle && nonStdToggle.checked;
    }

    function updateImeiEntryKindHints() {
        updateProbeBackgroundFromValue();
    }

    function syncTopActionButtons() {
        [
            [submitBtn, submitBtnTop],
            [cancelNewBtn, cancelNewBtnTop],
            [editBtn, editBtnTop],
            [cancelEditBtn, cancelEditBtnTop],
        ].forEach(function (pair) {
            const bottom = pair[0];
            const top = pair[1];
            if (!top) {
                return;
            }
            if (!bottom || bottom.hasAttribute('hidden')) {
                top.setAttribute('hidden', '');
            } else {
                top.removeAttribute('hidden');
            }
        });
        if (submitBtn && submitBtnTop) {
            submitBtnTop.textContent = submitBtn.textContent;
        }
    }

    function hideActionBtn(el) {
        if (el) {
            el.setAttribute('hidden', '');
        }
        syncTopActionButtons();
    }

    function showActionBtn(el) {
        if (el) {
            el.removeAttribute('hidden');
        }
        syncTopActionButtons();
    }

    function setFeedback(html, isError) {
        if (!feedback) {
            return;
        }
        feedback.innerHTML = html;
        feedback.classList.remove('text-red-600', 'text-green-700', 'text-gray-700');
        feedback.classList.add(isError ? 'text-red-600' : (html ? 'text-green-700' : 'text-gray-700'));
    }

    function setProbeVisualState(state) {
        if (!probe) {
            return;
        }
        probe.classList.remove('border-gray-300', 'bg-white', 'border-red-300', 'bg-red-50', 'border-green-300', 'bg-green-50');
        if (state === 'invalid') {
            probe.classList.add('border-red-300', 'bg-red-50');
            return;
        }
        if (state === 'valid') {
            probe.classList.add('border-green-300', 'bg-green-50');
            return;
        }
        probe.classList.add('border-gray-300', 'bg-white');
    }

    /** Matches App\Support\ImeiValidator (normalize + Luhn-style check digit). */
    function normalizeImeiDigits(input) {
        if (input == null) {
            return '';
        }
        return String(input).replace(/\D/g, '');
    }

    /** Matches App\Support\ImeiValidator::normalizeNonStandard (trim; strip nulls; remove space, dash, slash). */
    function normalizeNonStandardProbe(input) {
        if (input == null) {
            return '';
        }
        let s = String(input).trim().replace(/\0/g, '');
        s = s.replace(/ /g, '').replace(/-/g, '').replace(/\//g, '');

        return s;
    }

    function isValidImeiChecksum(input) {
        const digits = normalizeImeiDigits(input);
        if (digits.length !== 15) {
            return false;
        }
        let sum = 0;
        for (let i = 0; i < 14; i++) {
            let n = parseInt(digits.charAt(i), 10);
            if (i % 2 === 1) {
                n *= 2;
            }
            const s = String(n);
            for (let j = 0; j < s.length; j++) {
                sum += parseInt(s.charAt(j), 10);
            }
        }
        const check = (10 - (sum % 10)) % 10;
        return check === parseInt(digits.charAt(14), 10);
    }

    /** Empty = neutral; standard: green only for valid Luhn 15-digit; non-standard: green for 1–max digits after normalize. */
    function updateProbeBackgroundFromValue() {
        if (!probe) {
            return;
        }
        if (!probe.value.trim()) {
            setProbeVisualState('neutral');
            return;
        }
        if (isNonStandardToggleOn()) {
            const d = normalizeNonStandardProbe(probe.value);
            if (d.length >= 1 && d.length <= maxNonStandardImeiLength) {
                setProbeVisualState('valid');
            } else {
                setProbeVisualState('invalid');
            }
            return;
        }
        if (isValidImeiChecksum(probe.value)) {
            setProbeVisualState('valid');
        } else {
            setProbeVisualState('invalid');
        }
    }

    function getMutableElements() {
        return document.querySelectorAll(mutableSelector);
    }

    function setMutableReadonly(on) {
        getMutableElements().forEach(function (el) {
            if (el.tagName === 'SELECT') {
                el.disabled = on;
            } else {
                el.readOnly = on;
                if (el.tagName === 'TEXTAREA') {
                    el.readOnly = on;
                }
            }
            el.classList.toggle('bg-gray-50', on);
            el.classList.toggle('cursor-default', on);
        });
    }

    function clearMutableFields() {
        getMutableElements().forEach(function (el) {
            if (el.id === 'make' || el.id === 'model') {
                return;
            }
            if (el.tagName === 'SELECT') {
                el.selectedIndex = 0;
            } else {
                el.value = '';
            }
        });
        if (makeSelect) {
            makeSelect.selectedIndex = 0;
        }
        refreshModelSelectForMake(makeSelect ? makeSelect.value : '');
    }

    function populateFromRecord(record) {
        if (!record) {
            return;
        }
        const map = {
            sn: record.sn,
            item_code: record.item_code,
            type: record.type,
            status: record.status,
            location: record.location,
            date_in: record.date_in ? String(record.date_in).slice(0, 16) : '',
            stock_take_date: record.stock_take_date ?? '',
            phonenumber: record.phonenumber,
            ref: record.ref,
            staff: record.staff,
            ourON: record.ourON,
            salesON: record.salesON,
            cost_excl: record.cost_excl,
            selling_price: record.selling_price !== null && record.selling_price !== undefined ? String(record.selling_price) : '',
            notes: record.notes ?? '',
        };
        Object.keys(map).forEach(function (id) {
            const el = document.getElementById(id);
            if (el) {
                el.value = map[id] === null || map[id] === undefined ? '' : map[id];
            }
        });
        syncMakeModelFromRecord(record);
    }

    function setFormCreateMode() {
        if (recordIdInput) {
            recordIdInput.value = '';
        }
        if (form) {
            form.action = storeUrl;
        }
        if (methodSpoof) {
            methodSpoof.disabled = true;
        }
        if (existingBanner) {
            existingBanner.classList.add('hidden');
        }
        if (savedBanner) {
            savedBanner.classList.add('hidden');
        }
        if (dateHintNew) {
            dateHintNew.classList.remove('hidden');
        }
        if (nonStandardNewBanner) {
            nonStandardNewBanner.classList.add('hidden');
        }
        if (submitBtn) {
            submitBtn.textContent = 'Save IMEI';
            showActionBtn(submitBtn);
        }
        hideActionBtn(editBtn);
        hideActionBtn(cancelEditBtn);
        showActionBtn(cancelNewBtn);
        setMutableReadonly(false);
        setImeiDeleteActionsVisible(false);
    }

    function setFormUpdateMode(id) {
        if (recordIdInput) {
            recordIdInput.value = String(id);
        }
        if (form) {
            form.action = imeisResourceBase + '/' + encodeURIComponent(id);
        }
        if (methodSpoof) {
            methodSpoof.disabled = false;
        }
    }

    function showExistingReadonly(record) {
        setFormUpdateMode(record.id);
        if (nonStandardNewBanner) {
            nonStandardNewBanner.classList.add('hidden');
        }
        if (imeiFinal && record.imei) {
            imeiFinal.value = record.imei;
        }
        populateFromRecord(record);
        if (existingBanner) {
            existingBanner.classList.remove('hidden');
        }
        if (savedBanner) {
            savedBanner.classList.add('hidden');
        }
        if (dateHintNew) {
            dateHintNew.classList.add('hidden');
        }
        if (viewListLink && record.imei) {
            viewListLink.href = resultsBase + '?search=' + encodeURIComponent(String(record.imei)) + '&scope=all&date_scope=all';
        }
        setMutableReadonly(true);
        hideActionBtn(cancelNewBtn);
        hideActionBtn(submitBtn);
        showActionBtn(editBtn);
        showActionBtn(cancelEditBtn);
        setImeiDeleteActionsVisible(false);
    }

    function enterEditExisting() {
        setMutableReadonly(false);
        hideActionBtn(cancelNewBtn);
        if (submitBtn) {
            submitBtn.textContent = 'Save';
            showActionBtn(submitBtn);
        }
        hideActionBtn(editBtn);
        showActionBtn(cancelEditBtn);
        setImeiDeleteActionsVisible(true);
    }

    function showRest() {
        if (stepCheck) {
            stepCheck.classList.add('hidden');
        }
        if (stepRest) {
            stepRest.classList.remove('hidden');
        }
        if (topActionsWrap) {
            topActionsWrap.classList.remove('hidden');
        }
        syncTopActionButtons();
    }

    function showCheck() {
        if (stepRest) {
            stepRest.classList.add('hidden');
        }
        if (stepCheck) {
            stepCheck.classList.remove('hidden');
        }
        if (topActionsWrap) {
            topActionsWrap.classList.add('hidden');
        }
        if (probe) {
            probe.focus();
        }
        if (imeiFinal) {
            imeiFinal.value = '';
        }
        if (imeiNonStandardInput) {
            imeiNonStandardInput.value = '0';
        }
        if (nonStdToggle) {
            nonStdToggle.checked = false;
        }
        updateImeiEntryKindHints();
        clearMutableFields();
        setFormCreateMode();
        setFeedback('', false);
        updateProbeBackgroundFromValue();
        syncTopActionButtons();
    }

    async function runLookup() {
        if (!probe) {
            return;
        }

        const nonStandard = isNonStandardToggleOn();
        if (nonStandard) {
            const confirmed = window.confirm(
                'Add a non-standard IMEI? Only use this when the identifier is not a valid standard 15-digit IMEI. Continue?'
            );
            if (!confirmed) {
                return;
            }
        }

        const raw = probe.value.trim();
        if (!raw) {
            updateProbeBackgroundFromValue();
            setFeedback('Please enter an IMEI.', true);
            probe.focus();
            return;
        }

        setFeedback('Checking…', false);
        if (checkBtn) {
            checkBtn.disabled = true;
        }

        try {
            let url = lookupUrl + '?imei=' + encodeURIComponent(raw);
            if (nonStandard) {
                url += '&non_standard=1';
            }
            const res = await fetch(url, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            const data = await res.json();

            if (!data.valid) {
                updateProbeBackgroundFromValue();
                setFeedback(data.message ? String(data.message) : 'Invalid IMEI.', true);
                return;
            }

            updateProbeBackgroundFromValue();

            if (imeiNonStandardInput) {
                imeiNonStandardInput.value = nonStandard ? '1' : '0';
            }

            if (imeiFinal && data.canonical_imei) {
                imeiFinal.value = data.canonical_imei;
            }

            if (data.exists && data.record) {
                setFeedback('', false);
                showExistingReadonly(data.record);
                showRest();
                return;
            }

            setFormCreateMode();
            clearMutableFields();
            if (imeiFinal && data.canonical_imei) {
                imeiFinal.value = data.canonical_imei;
            }
            if (nonStandard && nonStandardNewBanner) {
                nonStandardNewBanner.classList.remove('hidden');
            }
            if (nonStandard) {
                setFeedback('This non-standard IMEI is not in the database. Fill in the details below.', false);
            } else {
                setFeedback('IMEI is valid and not in the database. Fill in the details below.', false);
            }
            showRest();
            const sn = document.getElementById('sn');
            if (sn) {
                sn.focus();
            }
        } catch (e) {
            updateProbeBackgroundFromValue();
            setFeedback('Could not check the IMEI. Try again.', true);
        } finally {
            if (checkBtn) {
                checkBtn.disabled = false;
            }
        }
    }

    // Always bind step-1 handlers: when the page loads with the full form open
    // ($formUnlocked true, e.g. after save), Exit returns to step 1 but listeners
    // must still work without a full navigation.
    if (checkBtn && probe) {
        checkBtn.addEventListener('click', runLookup);
        probe.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                runLookup();
            }
        });
        probe.addEventListener('input', updateProbeBackgroundFromValue);
        probe.addEventListener('paste', function () {
            requestAnimationFrame(updateProbeBackgroundFromValue);
        });
        updateProbeBackgroundFromValue();
    }

    if (nonStdToggle) {
        nonStdToggle.addEventListener('change', updateImeiEntryKindHints);
    }

    if (makeSelect) {
        makeSelect.addEventListener('change', onMakeChanged);
    }

    function goBackToImeiStep() {
        if (probe) {
            probe.value = '';
        }
        showCheck();
    }

    if (cancelNewBtn) {
        cancelNewBtn.addEventListener('click', goBackToImeiStep);
    }

    if (cancelNewBtnTop) {
        cancelNewBtnTop.addEventListener('click', goBackToImeiStep);
    }

    if (editBtn) {
        editBtn.addEventListener('click', enterEditExisting);
    }

    if (editBtnTop) {
        editBtnTop.addEventListener('click', function () {
            if (editBtn) {
                editBtn.click();
            }
        });
    }

    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function () {
            if (probe) {
                probe.value = '';
            }
            showCheck();
        });
    }

    if (cancelEditBtnTop) {
        cancelEditBtnTop.addEventListener('click', function () {
            if (cancelEditBtn) {
                cancelEditBtn.click();
            }
        });
    }

    if (formUnlocked && prefillRecordId && form && methodSpoof && !readonlyAfterSave) {
        form.action = imeisResourceBase + '/' + encodeURIComponent(prefillRecordId);
        methodSpoof.disabled = false;
        if (dateHintNew) {
            dateHintNew.classList.add('hidden');
        }
        if (viewListLink && imeiFinal && imeiFinal.value) {
            viewListLink.href = resultsBase + '?search=' + encodeURIComponent(imeiFinal.value) + '&scope=all&date_scope=all';
        }
        submitBtn.textContent = 'Save';
        showActionBtn(submitBtn);
        hideActionBtn(editBtn);
        showActionBtn(cancelEditBtn);
        hideActionBtn(cancelNewBtn);
        setMutableReadonly(false);
        setImeiDeleteActionsVisible(true);
        syncTopActionButtons();
    }

    updateImeiEntryKindHints();
    syncTopActionButtons();
});
</script>
@endsection
