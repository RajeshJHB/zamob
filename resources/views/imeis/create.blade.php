@extends($layout ?? 'layouts.app')

@php
    use App\Support\ImeiNewRecordDefaults;
    /** @var array<string, mixed>|null $viewRecord */
    $viewRecord = $viewRecord ?? null;
    $editableOnLoad = $editableOnLoad ?? false;
    $formUnlocked = $errors->any() || ! empty($viewRecord);
    $readonlyAfterSave = ! empty($viewRecord) && ! $errors->any() && ! $editableOnLoad;

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
    $dateInReadonly = $readonlyAfterSave || $updateRouteId === null;
    $dateInFieldClass = $dateInReadonly ? 'bg-gray-50 cursor-default' : '';

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

        $default = match ($key) {
            'location' => ImeiNewRecordDefaults::LOCATION,
            'type' => ImeiNewRecordDefaults::TYPE,
            'status' => ImeiNewRecordDefaults::STATUS,
            default => '',
        };

        return old($key, $default);
    };

    $customerDetailsEmpty = trim($imeiFieldValue('notes')) === '';
    $dealDetailsEmpty = trim($imeiFieldValue('ref')) === '';

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
    $statusInReferenceTable = $imeiStatuses->contains(fn (\App\Models\ImeiStatus $s): bool => $s->status === $currentStatusForSelect)
        || $currentStatusForSelect === \App\Support\ImeiDeletedStatus::VALUE;

    /** @var \Illuminate\Support\Collection<int, \App\Models\ImeiSaleType>|\Illuminate\Database\Eloquent\Collection<int, \App\Models\ImeiSaleType> $imeiSaleTypes */
    $imeiSaleTypes = $imeiSaleTypes ?? collect();
    $currentSaleTypeForSelect = $imeiFieldValue('cash_stock_type');
    $saleTypeInReferenceTable = $imeiSaleTypes->contains(fn (\App\Models\ImeiSaleType $saleType): bool => $saleType->sale_type === $currentSaleTypeForSelect);

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
    <div class="bg-white rounded-lg @if($embedded ?? false) p-0 @else shadow-md p-6 border border-gray-200 @endif">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-2">
            @unless($embedded ?? false)
                <h1 class="text-2xl font-bold">{{ $createPageHeading ?: 'Add IMEI' }}</h1>
            @endunless
            <div id="imei-form-actions-top-wrap" class="flex flex-wrap items-center justify-between gap-3 flex-1 min-w-0 shrink @unless($formUnlocked) hidden @endunless @if($embedded ?? false) w-full @endif">
                <div class="flex flex-wrap items-center gap-3">
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
                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <button type="button" id="imei-copy-last-btn-top" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400" hidden>
                        Copy Last
                    </button>
                @if($updateRouteId !== null)
                    <div class="imei-delete-action-wrap flex items-center shrink-0" @if($readonlyAfterSave && !$errors->any()) hidden @endif>
                        <button type="submit" form="imei-delete-form" id="imei-delete-btn-top" class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                onclick="return confirm('This will mark the record as Deleted. Continue?');">
                            Delete
                        </button>
                    </div>
                @endif
                </div>
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

        @if($updateRouteId !== null)
            <form id="imei-delete-form" method="POST" action="{{ route('imeis.destroy', ['imei' => $updateRouteId]) }}" class="hidden" aria-hidden="true">
                @csrf
                @method('DELETE')
                @if(! empty($returnQuery))
                    <input type="hidden" name="return_query" value="{{ $returnQuery }}">
                @endif
                @if($embedded ?? false)
                    <input type="hidden" name="embedded" value="1">
                @endif
            </form>
        @endif

        <form method="POST" action="{{ $formAction }}" class="space-y-6" id="imei-create-form">
            @csrf
            @if($embedded ?? false)
                <input type="hidden" name="embedded" value="1">
            @endif
            @if(! empty($returnQuery))
                <input type="hidden" name="return_query" value="{{ $returnQuery }}">
            @endif
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

                <div class="pb-4 border-b border-gray-200 space-y-3">
                    <p id="imei-date-hint-new" class="text-xs text-gray-500 @if($readonlyAfterSave) hidden @endif">Date in is recorded automatically when you save a new IMEI.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-[minmax(0,15rem)_1fr] gap-4 items-start">
                        <div>
                            <label for="date_in" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['date_in'] ?? 'Date in' }}</label>
                            <input type="datetime-local" name="date_in" id="date_in" value="{{ $imeiFieldValue('date_in') }}" @readonly($dateInReadonly) class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $dateInFieldClass }} @error('date_in') border-red-500 @enderror">
                            @error('date_in')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['location'] ?? 'Location' }}</label>
                            <select name="location" id="location" @disabled($readonlyAfterSave) class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full bg-white {{ $roFieldClass }} @error('location') border-red-500 @enderror">
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
                    </div>
                </div>

                <div class="space-y-4 pb-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold">Device</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="imei_final" class="block text-sm font-bold text-gray-700 mb-1">IMEI</label>
                            <input
                                type="text"
                                name="imei"
                                id="imei_final"
                                value="{{ $imeiFieldValue('imei') }}"
                                readonly
                                required
                                class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full bg-gray-50 text-gray-900 font-bold @error('imei') border-red-500 @enderror"
                            >
                            @error('imei')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="cash_stock_type" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['cash_stock_type'] ?? 'Sale type' }}</label>
                            <select name="cash_stock_type" id="cash_stock_type" @disabled($readonlyAfterSave) class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full bg-white {{ $roFieldClass }} @error('cash_stock_type') border-red-500 @enderror">
                                <option value="">— Select sale type —</option>
                                @if($currentSaleTypeForSelect !== '' && ! $saleTypeInReferenceTable)
                                    <option value="{{ $currentSaleTypeForSelect }}" selected>{{ $currentSaleTypeForSelect }} (not in list)</option>
                                @endif
                                @foreach($imeiSaleTypes as $imeiSaleType)
                                    <option value="{{ $imeiSaleType->sale_type }}" @selected($currentSaleTypeForSelect === $imeiSaleType->sale_type)>{{ $imeiSaleType->sale_type }}</option>
                                @endforeach
                            </select>
                            @error('cash_stock_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="sn" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['sn'] ?? 'Serial Number' }}</label>
                            <input type="text" name="sn" id="sn" value="{{ $imeiFieldValue('sn') }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('sn') border-red-500 @enderror">
                            @error('sn')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="item_code" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['item_code'] ?? 'Item code' }}</label>
                            <input type="text" name="item_code" id="item_code" value="{{ $imeiFieldValue('item_code') }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('item_code') border-red-500 @enderror">
                            @error('item_code')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-[1fr_3fr] gap-4">
                        <div class="min-w-0">
                            <label for="make" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['make'] ?? 'Make' }}</label>
                            <select name="make" id="make" @disabled($readonlyAfterSave) class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full bg-white font-bold {{ $roFieldClass }} @error('make') border-red-500 @enderror">
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
                        <div class="min-w-0">
                            <label for="model" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['model'] ?? 'Model' }}</label>
                            <select name="model" id="model" @disabled($readonlyAfterSave) class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full bg-white font-bold {{ $roFieldClass }} @error('model') border-red-500 @enderror">
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
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label for="cost_excl" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['cost_excl'] ?? 'Cost excl' }}</label>
                            <input type="text" name="cost_excl" id="cost_excl" value="{{ $imeiFieldValue('cost_excl') }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('cost_excl') border-red-500 @enderror">
                            @error('cost_excl')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['cost_incl'] ?? 'Cost incl' }}</label>
                            <p id="cost_incl_display" class="border border-gray-200 rounded px-3 py-2 shadow-sm w-full bg-gray-50 text-gray-900 min-h-[42px] flex items-center">
                                {{ \App\Support\ImeiCostIncl::format($imeiFieldValue('cost_excl')) ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <label for="selling_price" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['selling_price'] ?? 'Selling price' }}</label>
                            <input type="number" name="selling_price" id="selling_price" value="{{ $imeiFieldValue('selling_price') }}" step="1" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('selling_price') border-red-500 @enderror">
                            @error('selling_price')
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
                                @if($canSelectDeletedStatus ?? false)
                                    <option value="{{ \App\Support\ImeiDeletedStatus::VALUE }}" @selected($currentStatusForSelect === \App\Support\ImeiDeletedStatus::VALUE)>Deleted</option>
                                @endif
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="space-y-4 pb-4 border-b border-gray-200">
                    <div>
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <label for="notes" class="block text-sm font-medium text-gray-700">{{ $columnLabels['notes'] ?? 'Customer Details' }}</label>
                            <button
                                type="button"
                                id="imei-browse-contacts-btn"
                                @disabled(! $customerDetailsEmpty)
                                class="shrink-0 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-1 px-3 rounded text-sm border border-gray-300 disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:bg-gray-200 @if($readonlyAfterSave) hidden @endif"
                                title="{{ $customerDetailsEmpty ? '' : 'Only available when Customer Details is empty' }}"
                            >
                                Browse
                            </button>
                        </div>
                        <textarea name="notes" id="notes" rows="4" maxlength="{{ $customerDetailsMaxLength }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('notes') border-red-500 @enderror">{{ $imeiFieldValue('notes') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500" aria-live="polite">
                            <span id="notes-char-count" class="font-medium text-gray-700">0</span>
                            / {{ number_format($customerDetailsMaxLength) }} characters
                        </p>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="phonenumber" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['phonenumber'] ?? 'Deal Phone Number' }}</label>
                        <input type="text" name="phonenumber" id="phonenumber" value="{{ $imeiFieldValue('phonenumber') }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('phonenumber') border-red-500 @enderror">
                        @error('phonenumber')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <div class="flex items-center justify-between gap-3 mb-1">
                            <label for="ref" class="block text-sm font-medium text-gray-700">{{ $columnLabels['ref'] ?? 'Deal Details' }}</label>
                            <button
                                type="button"
                                id="imei-browse-deal-notes-btn"
                                disabled
                                class="shrink-0 bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-1 px-3 rounded text-sm border border-gray-300 disabled:opacity-70 disabled:cursor-not-allowed disabled:hover:bg-gray-200 @if($readonlyAfterSave) hidden @endif"
                                title="Only available when Deal Details is empty and a contact was chosen via Customer Details browse"
                            >
                                Browse notes
                            </button>
                        </div>
                        <textarea name="ref" id="ref" rows="4" maxlength="{{ $dealDetailsMaxLength }}" {{ $roAttr }} class="js-imei-mutable border border-gray-300 rounded px-3 py-2 shadow-sm w-full {{ $roFieldClass }} @error('ref') border-red-500 @enderror">{{ $imeiFieldValue('ref') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500" aria-live="polite">
                            <span id="ref-char-count" class="font-medium text-gray-700">0</span>
                            / {{ number_format($dealDetailsMaxLength) }} characters
                        </p>
                        @error('ref')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-4 pb-4 border-b border-gray-200">
                    <div>
                        <label for="staff" class="block text-sm font-medium text-gray-700 mb-1">{{ $columnLabels['staff'] ?? 'Staff' }}</label>
                        <input type="text" id="staff" value="{{ $imeiFieldValue('staff') }}" readonly class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-md bg-gray-50 cursor-default">
                        <p class="mt-1 text-xs text-gray-500">Users who created or edited this record (filled in automatically).</p>
                    </div>
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
                    <div class="flex flex-wrap items-center gap-3 shrink-0">
                        <button type="button" id="imei-copy-last-btn" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400" hidden>
                            Copy Last
                        </button>
                        @if($updateRouteId !== null)
                            <div class="imei-delete-action-wrap flex items-center shrink-0" @if($readonlyAfterSave && !$errors->any()) hidden @endif>
                                <button type="submit" form="imei-delete-form" id="imei-delete-btn-bottom" class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                        onclick="return confirm('This will mark the record as Deleted. Continue?');">
                                    Delete
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div
    id="imei-contact-browse-modal"
    class="fixed inset-0 z-50 hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="imei-contact-browse-title"
>
    <div class="absolute inset-0 bg-black/40" id="imei-contact-browse-backdrop"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[85vh] flex flex-col border border-gray-200">
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-gray-200">
                <h2 id="imei-contact-browse-title" class="text-xl font-bold text-gray-900">Browse contacts</h2>
                <button type="button" id="imei-contact-browse-close" class="text-gray-500 hover:text-gray-800 text-2xl leading-none" aria-label="Close">&times;</button>
            </div>
            <p class="px-6 pt-3 text-sm text-gray-600">All contacts (A&ndash;Z). Select one to fill Customer Details; you can still edit the text afterwards.</p>
            <div id="imei-contact-browse-loading" class="px-6 py-8 text-sm text-gray-500 hidden">Loading contacts…</div>
            <div id="imei-contact-browse-empty" class="px-6 py-8 text-sm text-gray-500 hidden">No contacts yet. Add contacts under Contacts first.</div>
            <div id="imei-contact-browse-table-wrap" class="px-6 py-4 overflow-auto flex-1 hidden">
                <table class="min-w-full border border-gray-300 text-sm">
                    <thead class="bg-gray-100 sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Company</th>
                            <th class="px-3 py-2 text-left font-semibold">First name</th>
                            <th class="px-3 py-2 text-left font-semibold">Surname</th>
                            <th class="px-3 py-2 text-left font-semibold">Telephone</th>
                            <th class="px-3 py-2 text-left font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody id="imei-contact-browse-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div
    id="imei-deal-notes-browse-modal"
    class="fixed inset-0 z-50 hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="imei-deal-notes-browse-title"
>
    <div class="absolute inset-0 bg-black/40" id="imei-deal-notes-browse-backdrop"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[85vh] flex flex-col border border-gray-200">
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-gray-200">
                <h2 id="imei-deal-notes-browse-title" class="text-xl font-bold text-gray-900">Browse service notes</h2>
                <button type="button" id="imei-deal-notes-browse-close" class="text-gray-500 hover:text-gray-800 text-2xl leading-none" aria-label="Close">&times;</button>
            </div>
            <p class="px-6 pt-3 text-sm text-gray-600">Service notes for the selected contact (newest first). Select one to copy into Deal Details; you can still edit the text afterwards.</p>
            <div id="imei-deal-notes-browse-loading" class="px-6 py-8 text-sm text-gray-500 hidden">Loading service notes…</div>
            <div id="imei-deal-notes-browse-empty" class="px-6 py-8 text-sm text-gray-500 hidden">This contact has no service notes yet.</div>
            <div id="imei-deal-notes-browse-table-wrap" class="px-6 py-4 overflow-auto flex-1 hidden">
                <table class="min-w-full border border-gray-300 text-sm">
                    <thead class="bg-gray-100 sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Note</th>
                            <th class="px-3 py-2 text-left font-semibold">Type</th>
                            <th class="px-3 py-2 text-left font-semibold">Heading</th>
                            <th class="px-3 py-2 text-left font-semibold">Recorded</th>
                            <th class="px-3 py-2 text-left font-semibold">Preview</th>
                            <th class="px-3 py-2 text-left font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody id="imei-deal-notes-browse-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div
    id="imei-unsaved-changes-modal"
    class="fixed inset-0 z-50 hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="imei-unsaved-changes-title"
>
    <div class="absolute inset-0 bg-black/40" id="imei-unsaved-changes-backdrop"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md border border-gray-200 p-6">
            <h2 id="imei-unsaved-changes-title" class="text-lg font-bold text-gray-900 mb-2">Unsaved changes</h2>
            <p class="text-sm text-gray-600 mb-6">You have changed this IMEI record. Save your changes before leaving, or discard them and exit.</p>
            <div class="flex flex-wrap justify-end gap-3">
                <button type="button" id="imei-unsaved-changes-cancel" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Keep editing
                </button>
                <button type="button" id="imei-unsaved-changes-discard" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Exit without saving
                </button>
                <button type="button" id="imei-unsaved-changes-save" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const embeddedMode = @json($embedded ?? false);
    const openedInEditMode = @json($editableOnLoad ?? false);
    const embeddedCloseOnLoad = @json($embeddedClose ?? false);
    const lookupUrl = @json(route('imeis.lookup'));
    const contactsBrowseUrl = @json(route('imeis.contacts.browse'));
    const copyLastUrl = @json(route('imeis.last-for-copy'));
    const resultsBase = @json(route('imeis.index'));
    const returnListUrl = @json($returnListUrl ?? null);
    const returnQueryEncoded = @json($returnQuery ?? null);
    const storeUrl = @json(route('imeis.store'));
    const imeisResourceBase = @json(rtrim(url('/imeis'), '/'));
    const formUnlocked = @json($formUnlocked);
    const prefillRecordId = @json($prefillRecordId);
    const readonlyAfterSave = @json($readonlyAfterSave);
    const maxNonStandardImeiLength = @json(\App\Support\ImeiValidator::MAX_NON_STANDARD_IMEI_LENGTH);
    const imeiModelsCatalog = @json($imeiModelsCatalogForScript);
    const newRecordSelectDefaults = @json(ImeiNewRecordDefaults::selectFields());
    const vatPercent = @json($vatPercent ?? \App\Support\ImeiCostIncl::DEFAULT_VAT_PERCENT);
    const customerDetailsMaxLength = @json($customerDetailsMaxLength ?? \App\Support\ImeiTextLimits::CUSTOMER_DETAILS_MAX);
    const dealDetailsMaxLength = @json($dealDetailsMaxLength ?? \App\Support\ImeiTextLimits::DEAL_DETAILS_MAX);
    const costExclInput = document.getElementById('cost_excl');
    const costInclDisplay = document.getElementById('cost_incl_display');
    let currentViewRecord = @json($viewRecord);
    let viewReadonlyBanner = readonlyAfterSave ? 'saved' : 'existing';
    let isEditingRecord = false;
    let editBaselineSnapshot = null;

    function notifyParentClose(reload) {
        if (! embeddedMode || window.parent === window) {
            return false;
        }

        window.parent.postMessage({
            type: 'imei-form-close',
            reload: !! reload,
        }, window.location.origin);

        return true;
    }

    const form = document.getElementById('imei-create-form');
    const stepCheck = document.getElementById('imei-step-check');
    const stepRest = document.getElementById('imei-step-rest');
    const probe = document.getElementById('imei_probe');
    const checkBtn = document.getElementById('imei-check-btn');
    const feedback = document.getElementById('imei-lookup-feedback');
    const imeiFinal = document.getElementById('imei_final');
    const cancelNewBtn = document.getElementById('imei-cancel-new-btn');
    const cancelNewBtnTop = document.getElementById('imei-cancel-new-btn-top');
    const copyLastBtn = document.getElementById('imei-copy-last-btn');
    const copyLastBtnTop = document.getElementById('imei-copy-last-btn-top');
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

    function isActionBtnHidden(el) {
        return !el || el.hasAttribute('hidden') || el.classList.contains('hidden');
    }

    function syncTopActionButtons() {
        [
            [submitBtn, submitBtnTop],
            [cancelNewBtn, cancelNewBtnTop],
            [editBtn, editBtnTop],
            [cancelEditBtn, cancelEditBtnTop],
            [copyLastBtn, copyLastBtnTop],
        ].forEach(function (pair) {
            const bottom = pair[0];
            const top = pair[1];
            if (!top) {
                return;
            }
            if (isActionBtnHidden(bottom)) {
                top.setAttribute('hidden', '');
                top.classList.add('hidden');
            } else {
                top.removeAttribute('hidden');
                top.classList.remove('hidden');
            }
        });
        if (submitBtn && submitBtnTop) {
            submitBtnTop.textContent = submitBtn.textContent;
        }
    }

    function hideActionBtn(el) {
        if (el) {
            el.setAttribute('hidden', '');
            el.classList.add('hidden');
        }
        syncTopActionButtons();
    }

    function showActionBtn(el) {
        if (el) {
            el.removeAttribute('hidden');
            el.classList.remove('hidden');
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

        const browseContactsBtn = document.getElementById('imei-browse-contacts-btn');
        if (browseContactsBtn) {
            browseContactsBtn.classList.toggle('hidden', on);
        }

        const dealBrowseBtn = document.getElementById('imei-browse-deal-notes-btn');
        if (dealBrowseBtn) {
            dealBrowseBtn.classList.toggle('hidden', on);
        }

        updateImeiBrowseButtonsState();
    }

    function defaultDateInLocalValue() {
        const d = new Date();
        const pad = function (n) {
            return String(n).padStart(2, '0');
        };

        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
            + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function setSelectValueIfPresent(selectEl, value) {
        if (!selectEl || !value) {
            return;
        }
        const hasOption = Array.from(selectEl.options).some(function (option) {
            return option.value === value;
        });
        if (hasOption) {
            selectEl.value = value;
        } else {
            selectEl.selectedIndex = 0;
        }
    }

    function clearMutableFields() {
        getMutableElements().forEach(function (el) {
            if (el.id === 'make' || el.id === 'model') {
                return;
            }
            if (el.id === 'date_in') {
                el.value = '';

                return;
            }
            if (el.id === 'location' || el.id === 'type' || el.id === 'status') {
                setSelectValueIfPresent(el, newRecordSelectDefaults[el.id]);

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
        updateCostInclDisplay();
    }

    function parseCostExclAmount(value) {
        if (value === null || value === undefined) {
            return null;
        }
        const normalized = String(value).trim().replace(/\s+/g, '').replace(',', '.');
        if (normalized === '' || Number.isNaN(Number(normalized))) {
            return null;
        }

        return Number(normalized);
    }

    function formatCostInclAmount(amount) {
        const formatted = amount.toFixed(2);
        return formatted.replace(/\.?0+$/, '') || '0';
    }

    function updateCostInclDisplay() {
        if (!costInclDisplay) {
            return;
        }
        const amount = costExclInput ? parseCostExclAmount(costExclInput.value) : null;
        if (amount === null) {
            costInclDisplay.textContent = '—';

            return;
        }
        const incl = amount * (1 + (vatPercent / 100));
        costInclDisplay.textContent = formatCostInclAmount(incl);
    }

    if (costExclInput) {
        costExclInput.addEventListener('input', updateCostInclDisplay);
        costExclInput.addEventListener('change', updateCostInclDisplay);
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
        const cashStockEl = document.getElementById('cash_stock_type');
        if (cashStockEl) {
            cashStockEl.value = record.cash_stock_type == null ? '' : String(record.cash_stock_type);
        }
        const dateInEl = document.getElementById('date_in');
        if (dateInEl && record.date_in) {
            dateInEl.value = String(record.date_in);
        }
        updateCostInclDisplay();

        const notesElAfterPopulate = document.getElementById('notes');
        const refElAfterPopulate = document.getElementById('ref');
        if (notesElAfterPopulate) {
            notesElAfterPopulate.dispatchEvent(new Event('input'));
        }
        if (refElAfterPopulate) {
            refElAfterPopulate.dispatchEvent(new Event('input'));
        }
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
        showActionBtn(copyLastBtn);
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

    function appendReturnQueryToUrl(url) {
        if (!returnQueryEncoded) {
            return url;
        }

        const separator = url.indexOf('?') >= 0 ? '&' : '?';

        return url + separator + 'return_query=' + encodeURIComponent(returnQueryEncoded);
    }

    function goBackToResultsList() {
        if (embeddedMode && returnListUrl === 'embedded:close') {
            notifyParentClose(true);

            return true;
        }

        if (returnListUrl) {
            window.location.href = returnListUrl;

            return true;
        }

        return false;
    }

    function resolveViewRecordId() {
        if (currentViewRecord && currentViewRecord.id) {
            return String(currentViewRecord.id);
        }
        if (prefillRecordId) {
            return String(prefillRecordId);
        }
        if (recordIdInput && recordIdInput.value) {
            return String(recordIdInput.value);
        }

        return null;
    }

    function serializeEditFormState() {
        if (!form) {
            return '';
        }

        const fd = new FormData(form);
        const parts = [];

        fd.forEach(function (value, key) {
            if (key === '_token' || key === '_method') {
                return;
            }
            parts.push(key + '=' + String(value));
        });
        parts.sort();

        return parts.join('\n');
    }

    function captureEditBaseline() {
        editBaselineSnapshot = serializeEditFormState();
    }

    function hasUnsavedEditChanges() {
        if (!isEditingRecord || editBaselineSnapshot === null) {
            return false;
        }

        return serializeEditFormState() !== editBaselineSnapshot;
    }

    const unsavedChangesModal = document.getElementById('imei-unsaved-changes-modal');
    const unsavedChangesBackdrop = document.getElementById('imei-unsaved-changes-backdrop');
    const unsavedChangesCancel = document.getElementById('imei-unsaved-changes-cancel');
    const unsavedChangesDiscard = document.getElementById('imei-unsaved-changes-discard');
    const unsavedChangesSave = document.getElementById('imei-unsaved-changes-save');

    function openUnsavedChangesModal() {
        if (unsavedChangesModal) {
            unsavedChangesModal.classList.remove('hidden');
        }
    }

    function closeUnsavedChangesModal() {
        if (unsavedChangesModal) {
            unsavedChangesModal.classList.add('hidden');
        }
    }

    function performExitEdit() {
        isEditingRecord = false;
        editBaselineSnapshot = null;

        if (openedInEditMode) {
            if (goBackToResultsList()) {
                return;
            }

            if (embeddedMode) {
                notifyParentClose(true);

                return;
            }

            const recordId = resolveViewRecordId();
            if (recordId) {
                const imeiVal = (currentViewRecord && currentViewRecord.imei)
                    || (imeiFinal && imeiFinal.value)
                    || '';
                const listUrl = imeiVal
                    ? resultsBase + '?search=' + encodeURIComponent(String(imeiVal)) + '&scope=all&date_scope=all'
                    : resultsBase;
                window.location.href = listUrl;

                return;
            }

            showCheck();

            return;
        }

        if (currentViewRecord) {
            populateFromRecord(currentViewRecord);
            showRecordReadonly(currentViewRecord, viewReadonlyBanner);

            return;
        }

        if (goBackToResultsList()) {
            return;
        }

        if (embeddedMode) {
            notifyParentClose(true);

            return;
        }

        showCheck();
    }

    function exitEditToViewMode() {
        if (!isEditingRecord) {
            performExitEdit();

            return;
        }

        if (hasUnsavedEditChanges()) {
            openUnsavedChangesModal();

            return;
        }

        performExitEdit();
    }

    function showRecordReadonly(record, bannerKind) {
        currentViewRecord = record;
        viewReadonlyBanner = bannerKind;
        isEditingRecord = false;
        setFormUpdateMode(record.id);
        if (nonStandardNewBanner) {
            nonStandardNewBanner.classList.add('hidden');
        }
        if (imeiFinal && record.imei) {
            imeiFinal.value = record.imei;
        }
        populateFromRecord(record);
        if (existingBanner) {
            existingBanner.classList.toggle('hidden', bannerKind !== 'existing');
        }
        if (savedBanner) {
            savedBanner.classList.toggle('hidden', bannerKind !== 'saved');
        }
        if (dateHintNew) {
            dateHintNew.classList.add('hidden');
        }
        if (viewListLink) {
            if (returnListUrl) {
                viewListLink.href = returnListUrl;
            } else if (record.imei) {
                viewListLink.href = resultsBase + '?search=' + encodeURIComponent(String(record.imei)) + '&scope=all&date_scope=all';
            }
        }
        setMutableReadonly(true);
        hideActionBtn(cancelNewBtn);
        hideActionBtn(submitBtn);
        hideActionBtn(copyLastBtn);
        showActionBtn(editBtn);
        showActionBtn(cancelEditBtn);
        setImeiDeleteActionsVisible(false);
    }

    function showExistingReadonly(record) {
        showRecordReadonly(record, 'existing');
    }

    function enterEditExisting() {
        isEditingRecord = true;
        setMutableReadonly(false);
        hideActionBtn(cancelNewBtn);
        hideActionBtn(copyLastBtn);
        if (submitBtn) {
            submitBtn.textContent = 'Save';
            showActionBtn(submitBtn);
        }
        hideActionBtn(editBtn);
        showActionBtn(cancelEditBtn);
        setImeiDeleteActionsVisible(true);
        captureEditBaseline();
    }

    async function copyLastRecord() {
        if (copyLastBtn) {
            copyLastBtn.disabled = true;
        }
        if (copyLastBtnTop) {
            copyLastBtnTop.disabled = true;
        }

        try {
            const res = await fetch(copyLastUrl, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            const data = await res.json();

            if (!res.ok || !data.record) {
                setFeedback(data.message ? String(data.message) : 'No previous record to copy.', true);
                return;
            }

            populateFromRecord(data.record);
            setFeedback('Copied fields from the last saved record. IMEI is unchanged.', false);
        } catch (e) {
            setFeedback('Could not load the last record. Try again.', true);
        } finally {
            if (copyLastBtn) {
                copyLastBtn.disabled = false;
            }
            if (copyLastBtnTop) {
                copyLastBtnTop.disabled = false;
            }
        }
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
        hideActionBtn(copyLastBtn);
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

            if (data.deleted && ! data.record) {
                setFeedback(data.message ? String(data.message) : 'This IMEI cannot be added.', true);
                return;
            }

            if (data.exists && data.record) {
                if (data.deleted && data.message) {
                    setFeedback(String(data.message), true);
                } else {
                    setFeedback('', false);
                }
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

    if (copyLastBtn) {
        copyLastBtn.addEventListener('click', copyLastRecord);
    }

    if (copyLastBtnTop) {
        copyLastBtnTop.addEventListener('click', copyLastRecord);
    }

    const contactBrowseModal = document.getElementById('imei-contact-browse-modal');
    const contactBrowseBackdrop = document.getElementById('imei-contact-browse-backdrop');
    const contactBrowseClose = document.getElementById('imei-contact-browse-close');
    const contactBrowseBtn = document.getElementById('imei-browse-contacts-btn');
    const contactBrowseLoading = document.getElementById('imei-contact-browse-loading');
    const contactBrowseEmpty = document.getElementById('imei-contact-browse-empty');
    const contactBrowseTableWrap = document.getElementById('imei-contact-browse-table-wrap');
    const contactBrowseTbody = document.getElementById('imei-contact-browse-tbody');
    let contactsBrowseLoaded = false;

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text === null || text === undefined ? '' : String(text);

        return div.innerHTML;
    }

    function truncateToMaxLength(value, maxLength) {
        const text = value === null || value === undefined ? '' : String(value);

        return text.length > maxLength ? text.slice(0, maxLength) : text;
    }

    function setupCharCounter(textareaId, counterId, maxLength) {
        const textarea = document.getElementById(textareaId);
        const counter = document.getElementById(counterId);

        if (!textarea || !counter) {
            return;
        }

        function updateCharCounter() {
            if (textarea.value.length > maxLength) {
                textarea.value = textarea.value.slice(0, maxLength);
            }

            const length = textarea.value.length;
            counter.textContent = String(length);
            counter.classList.toggle('text-red-600', length >= maxLength);
            counter.classList.toggle('text-gray-700', length < maxLength);
        }

        textarea.addEventListener('input', updateCharCounter);
        textarea.addEventListener('change', updateCharCounter);
        updateCharCounter();
    }

    setupCharCounter('notes', 'notes-char-count', customerDetailsMaxLength);
    setupCharCounter('ref', 'ref-char-count', dealDetailsMaxLength);

    let selectedContactIdForDealBrowse = null;
    let dealNotesBrowseLoadedContactId = null;

    const dealNotesBrowseBtn = document.getElementById('imei-browse-deal-notes-btn');
    const dealNotesBrowseModal = document.getElementById('imei-deal-notes-browse-modal');
    const dealNotesBrowseBackdrop = document.getElementById('imei-deal-notes-browse-backdrop');
    const dealNotesBrowseClose = document.getElementById('imei-deal-notes-browse-close');
    const dealNotesBrowseLoading = document.getElementById('imei-deal-notes-browse-loading');
    const dealNotesBrowseEmpty = document.getElementById('imei-deal-notes-browse-empty');
    const dealNotesBrowseTableWrap = document.getElementById('imei-deal-notes-browse-table-wrap');
    const dealNotesBrowseTbody = document.getElementById('imei-deal-notes-browse-tbody');

    function isImeiFieldEmpty(fieldId) {
        const el = document.getElementById(fieldId);

        return !el || el.value.trim() === '';
    }

    function isImeiFormReadonlyForBrowse() {
        const notesEl = document.getElementById('notes');

        return notesEl !== null && notesEl.readOnly;
    }

    function setCustomerBrowseEnabled(enabled) {
        if (!contactBrowseBtn) {
            return;
        }

        contactBrowseBtn.disabled = !enabled;
        if (enabled) {
            contactBrowseBtn.removeAttribute('title');
        } else {
            contactBrowseBtn.setAttribute('title', 'Only available when Customer Details is empty');
        }
    }

    function setDealNotesBrowseEnabled(enabled) {
        if (!dealNotesBrowseBtn) {
            return;
        }

        dealNotesBrowseBtn.disabled = !enabled;
        if (enabled) {
            dealNotesBrowseBtn.removeAttribute('title');

            return;
        }

        if (!isImeiFieldEmpty('ref')) {
            dealNotesBrowseBtn.setAttribute('title', 'Only available when Deal Details is empty');
        } else if (!selectedContactIdForDealBrowse) {
            dealNotesBrowseBtn.setAttribute('title', 'Choose a contact via Customer Details browse first (Customer Details must be empty)');
        } else {
            dealNotesBrowseBtn.setAttribute('title', 'Only available when Deal Details is empty');
        }
    }

    function updateImeiBrowseButtonsState() {
        if (isImeiFormReadonlyForBrowse()) {
            setCustomerBrowseEnabled(false);
            setDealNotesBrowseEnabled(false);

            return;
        }

        const notesEmpty = isImeiFieldEmpty('notes');
        const refEmpty = isImeiFieldEmpty('ref');

        if (notesEmpty) {
            selectedContactIdForDealBrowse = null;
            dealNotesBrowseLoadedContactId = null;
        }

        setCustomerBrowseEnabled(notesEmpty);
        setDealNotesBrowseEnabled(refEmpty && selectedContactIdForDealBrowse !== null);
    }

    const notesFieldForBrowse = document.getElementById('notes');
    const refFieldForBrowse = document.getElementById('ref');
    if (notesFieldForBrowse) {
        notesFieldForBrowse.addEventListener('input', updateImeiBrowseButtonsState);
    }
    if (refFieldForBrowse) {
        refFieldForBrowse.addEventListener('input', updateImeiBrowseButtonsState);
    }

    function closeContactBrowseModal() {
        if (contactBrowseModal) {
            contactBrowseModal.classList.add('hidden');
        }
    }

    function openContactBrowseModal() {
        if (!contactBrowseModal || !isImeiFieldEmpty('notes')) {
            return;
        }
        contactBrowseModal.classList.remove('hidden');
        if (!contactsBrowseLoaded) {
            loadContactsForBrowse();
        }
    }

    function serviceNotesBrowseUrl(contactId) {
        return imeisResourceBase + '/contacts/' + encodeURIComponent(String(contactId)) + '/service-notes/browse';
    }

    function applyContactToCustomerDetails(contact) {
        const notesEl = document.getElementById('notes');
        const phoneEl = document.getElementById('phonenumber');

        if (notesEl) {
            notesEl.value = truncateToMaxLength(contact.customer_details || '', customerDetailsMaxLength);
            notesEl.dispatchEvent(new Event('input'));
        }
        if (phoneEl) {
            phoneEl.value = contact.telephone_1 || '';
        }

        selectedContactIdForDealBrowse = contact.id;
        dealNotesBrowseLoadedContactId = null;
        closeContactBrowseModal();
        updateImeiBrowseButtonsState();
    }

    function applyServiceNoteToDealDetails(note) {
        const refEl = document.getElementById('ref');
        if (refEl) {
            refEl.value = truncateToMaxLength(note.deal_details_text || '', dealDetailsMaxLength);
            refEl.dispatchEvent(new Event('input'));
        }
        closeDealNotesBrowseModal();
    }

    function closeDealNotesBrowseModal() {
        if (dealNotesBrowseModal) {
            dealNotesBrowseModal.classList.add('hidden');
        }
    }

    function openDealNotesBrowseModal() {
        if (!dealNotesBrowseModal || !selectedContactIdForDealBrowse || !isImeiFieldEmpty('ref')) {
            return;
        }
        dealNotesBrowseModal.classList.remove('hidden');
        if (dealNotesBrowseLoadedContactId !== selectedContactIdForDealBrowse) {
            loadServiceNotesForDealBrowse(selectedContactIdForDealBrowse);
        }
    }

    function renderDealNotesBrowseRows(notes) {
        if (!dealNotesBrowseTbody) {
            return;
        }
        dealNotesBrowseTbody.innerHTML = '';

        notes.forEach(function (note) {
            const preview = (note.body || '').length > 120
                ? String(note.body).slice(0, 120) + '…'
                : (note.body || '—');
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 border-t border-gray-200';
            tr.innerHTML =
                '<td class="px-3 py-2 whitespace-nowrap font-mono text-xs">' + escapeHtml(note.note_number || '—') + '</td>' +
                '<td class="px-3 py-2">' + escapeHtml(note.note_type || '—') + '</td>' +
                '<td class="px-3 py-2">' + escapeHtml(note.heading || '—') + '</td>' +
                '<td class="px-3 py-2 whitespace-nowrap">' + escapeHtml(note.noted_at || '—') + '</td>' +
                '<td class="px-3 py-2 max-w-xs truncate" title="' + escapeHtml(note.body || '') + '">' + escapeHtml(preview) + '</td>' +
                '<td class="px-3 py-2 whitespace-nowrap">' +
                    '<button type="button" class="text-blue-600 hover:text-blue-800 font-medium imei-deal-note-pick-btn">Select</button>' +
                '</td>';
            const pickBtn = tr.querySelector('.imei-deal-note-pick-btn');
            if (pickBtn) {
                pickBtn.addEventListener('click', function () {
                    applyServiceNoteToDealDetails(note);
                });
            }
            dealNotesBrowseTbody.appendChild(tr);
        });
    }

    async function loadServiceNotesForDealBrowse(contactId) {
        if (dealNotesBrowseLoading) {
            dealNotesBrowseLoading.classList.remove('hidden');
        }
        if (dealNotesBrowseEmpty) {
            dealNotesBrowseEmpty.classList.add('hidden');
            dealNotesBrowseEmpty.textContent = 'This contact has no service notes yet.';
        }
        if (dealNotesBrowseTableWrap) {
            dealNotesBrowseTableWrap.classList.add('hidden');
        }

        try {
            const res = await fetch(serviceNotesBrowseUrl(contactId), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            const notes = data.notes || [];
            dealNotesBrowseLoadedContactId = contactId;

            if (notes.length === 0) {
                if (dealNotesBrowseEmpty) {
                    dealNotesBrowseEmpty.classList.remove('hidden');
                }
            } else {
                renderDealNotesBrowseRows(notes);
                if (dealNotesBrowseTableWrap) {
                    dealNotesBrowseTableWrap.classList.remove('hidden');
                }
            }
        } catch (e) {
            if (dealNotesBrowseEmpty) {
                dealNotesBrowseEmpty.textContent = 'Could not load service notes. Try again.';
                dealNotesBrowseEmpty.classList.remove('hidden');
            }
        } finally {
            if (dealNotesBrowseLoading) {
                dealNotesBrowseLoading.classList.add('hidden');
            }
        }
    }

    if (dealNotesBrowseBtn) {
        dealNotesBrowseBtn.addEventListener('click', openDealNotesBrowseModal);
    }
    if (dealNotesBrowseClose) {
        dealNotesBrowseClose.addEventListener('click', closeDealNotesBrowseModal);
    }
    if (dealNotesBrowseBackdrop) {
        dealNotesBrowseBackdrop.addEventListener('click', closeDealNotesBrowseModal);
    }

    updateImeiBrowseButtonsState();

    function renderContactBrowseRows(contacts) {
        if (!contactBrowseTbody) {
            return;
        }
        contactBrowseTbody.innerHTML = '';

        contacts.forEach(function (contact) {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 border-t border-gray-200';
            tr.innerHTML =
                '<td class="px-3 py-2">' + escapeHtml(contact.company_name || '—') + '</td>' +
                '<td class="px-3 py-2">' + escapeHtml(contact.first_name || '—') + '</td>' +
                '<td class="px-3 py-2">' + escapeHtml(contact.surname || '—') + '</td>' +
                '<td class="px-3 py-2">' + escapeHtml(contact.telephone_1 || '—') + '</td>' +
                '<td class="px-3 py-2 whitespace-nowrap">' +
                    '<button type="button" class="text-blue-600 hover:text-blue-800 font-medium imei-contact-pick-btn">Select</button>' +
                '</td>';
            const pickBtn = tr.querySelector('.imei-contact-pick-btn');
            if (pickBtn) {
                pickBtn.addEventListener('click', function () {
                    applyContactToCustomerDetails(contact);
                });
            }
            contactBrowseTbody.appendChild(tr);
        });
    }

    async function loadContactsForBrowse() {
        if (contactBrowseLoading) {
            contactBrowseLoading.classList.remove('hidden');
        }
        if (contactBrowseEmpty) {
            contactBrowseEmpty.classList.add('hidden');
        }
        if (contactBrowseTableWrap) {
            contactBrowseTableWrap.classList.add('hidden');
        }

        try {
            const res = await fetch(contactsBrowseUrl, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            const data = await res.json();
            const contacts = data.contacts || [];
            contactsBrowseLoaded = true;

            if (contacts.length === 0) {
                if (contactBrowseEmpty) {
                    contactBrowseEmpty.classList.remove('hidden');
                }
            } else {
                renderContactBrowseRows(contacts);
                if (contactBrowseTableWrap) {
                    contactBrowseTableWrap.classList.remove('hidden');
                }
            }
        } catch (e) {
            if (contactBrowseEmpty) {
                contactBrowseEmpty.textContent = 'Could not load contacts. Try again.';
                contactBrowseEmpty.classList.remove('hidden');
            }
        } finally {
            if (contactBrowseLoading) {
                contactBrowseLoading.classList.add('hidden');
            }
        }
    }

    if (contactBrowseBtn) {
        contactBrowseBtn.addEventListener('click', openContactBrowseModal);
    }
    if (contactBrowseClose) {
        contactBrowseClose.addEventListener('click', closeContactBrowseModal);
    }
    if (contactBrowseBackdrop) {
        contactBrowseBackdrop.addEventListener('click', closeContactBrowseModal);
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
        cancelEditBtn.addEventListener('click', exitEditToViewMode);
    }

    if (cancelEditBtnTop) {
        cancelEditBtnTop.addEventListener('click', function () {
            if (cancelEditBtn) {
                cancelEditBtn.click();
            }
        });
    }

    if (unsavedChangesCancel) {
        unsavedChangesCancel.addEventListener('click', closeUnsavedChangesModal);
    }

    if (unsavedChangesBackdrop) {
        unsavedChangesBackdrop.addEventListener('click', closeUnsavedChangesModal);
    }

    if (unsavedChangesDiscard) {
        unsavedChangesDiscard.addEventListener('click', function () {
            closeUnsavedChangesModal();
            performExitEdit();
        });
    }

    if (unsavedChangesSave) {
        unsavedChangesSave.addEventListener('click', function () {
            closeUnsavedChangesModal();
            if (form && typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else if (form) {
                form.submit();
            }
        });
    }

    if (formUnlocked && prefillRecordId && form && methodSpoof && !readonlyAfterSave) {
        isEditingRecord = true;
        form.action = imeisResourceBase + '/' + encodeURIComponent(prefillRecordId);
        methodSpoof.disabled = false;
        if (dateHintNew) {
            dateHintNew.classList.add('hidden');
        }
        if (viewListLink) {
            if (returnListUrl) {
                viewListLink.href = returnListUrl;
            } else if (imeiFinal && imeiFinal.value) {
                viewListLink.href = resultsBase + '?search=' + encodeURIComponent(imeiFinal.value) + '&scope=all&date_scope=all';
            }
        }
        submitBtn.textContent = 'Save';
        showActionBtn(submitBtn);
        hideActionBtn(editBtn);
        showActionBtn(cancelEditBtn);
        hideActionBtn(cancelNewBtn);
        hideActionBtn(copyLastBtn);
        setMutableReadonly(false);
        setImeiDeleteActionsVisible(true);
        captureEditBaseline();
        syncTopActionButtons();
    }

    if (formUnlocked && !readonlyAfterSave && prefillRecordId === null) {
        showActionBtn(copyLastBtn);
    }

    updateImeiEntryKindHints();
    syncTopActionButtons();

    if (embeddedCloseOnLoad) {
        notifyParentClose(true);
    }
});
</script>
@endsection
