@php
    $colourOptions = $ageColourOptions ?? \App\Support\ImeiInShopAgeColour::options();
    $selectedKey = $selected ?? '';
    $selectedSwatch = $colourOptions[$selectedKey]['swatch'] ?? 'bg-gray-200';
@endphp
<div class="flex items-center gap-1.5 min-w-0 age-colour-select-wrap">
    <span class="js-age-colour-swatch inline-block h-5 w-5 rounded border border-gray-300 shrink-0 {{ $selectedSwatch }}" aria-hidden="true"></span>
    <select
        name="{{ $name }}"
        class="js-age-colour-select border border-gray-300 rounded px-2 py-1 shadow-sm bg-white text-xs min-w-[6.5rem] @error($name) border-red-500 @enderror disabled:bg-gray-100 disabled:text-gray-600"
        @required(empty($readOnly))
        @disabled($readOnly ?? false)
    >
        @foreach($colourOptions as $key => $option)
            <option value="{{ $key }}" data-swatch="{{ $option['swatch'] }}" @selected($selectedKey === $key)>{{ $option['label'] }}</option>
        @endforeach
    </select>
    @error($name)
        <p class="text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
