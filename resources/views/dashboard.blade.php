@extends('layouts.app')

@section('title', 'Home')

@section('content')
@php
    use App\Support\CashDevicesTable;
    use App\Support\ImeiCostIncl;
    use App\Support\ImeiInShopAgeHighlight;

    $sortableHeaders = [
        'date_in' => 'Date In',
        'make' => 'Make',
        'model' => 'Model',
        'ref' => 'Phone Spec',
        'imei' => 'IMEI',
        'selling_price' => 'Selling Price',
    ];
@endphp
<div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
        <h2 class="text-3xl font-bold text-center sm:text-left">Cash Devices</h2>
        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2 justify-center sm:justify-end">
            @if(request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif
            @if(request('dir'))
                <input type="hidden" name="dir" value="{{ request('dir') }}">
            @endif
            <label for="imei_type_id" class="text-sm font-medium text-gray-700 whitespace-nowrap">IMEI type</label>
            <select
                name="imei_type_id"
                id="imei_type_id"
                onchange="this.form.submit()"
                class="border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white min-w-[10rem]"
            >
                <option value="ALL" @selected($selectedImeiTypeId === null)>ALL</option>
                @foreach($imeiTypes as $imeiTypeOption)
                    <option value="{{ $imeiTypeOption->id }}" @selected($selectedImeiTypeId === $imeiTypeOption->id)>
                        {{ $imeiTypeOption->type }}
                    </option>
                @endforeach
            </select>
            <label for="sale_type" class="text-sm font-medium text-gray-700 whitespace-nowrap">Sale type</label>
            <select
                name="sale_type"
                id="sale_type"
                onchange="this.form.submit()"
                class="border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white min-w-[10rem]"
            >
                <option value="ALL" @selected($selectedSaleType === 'ALL')>ALL</option>
                @foreach($saleTypes as $saleType)
                    <option value="{{ $saleType->sale_type }}" @selected($selectedSaleType === $saleType->sale_type)>
                        {{ $saleType->sale_type }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    <p class="text-sm text-gray-600 mb-3">Cost incl is hidden. Click a Cost incl cell to show cost incl for all rows; click anywhere else to hide again.</p>

    <div class="overflow-x-auto">
        <table id="cash-devices-table" class="min-w-full border border-gray-300 text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold border-b border-gray-300">Edit</th>
                    @foreach($sortableHeaders as $column => $label)
                        <th class="px-3 py-2 text-left font-semibold border-b border-gray-300">
                            <a href="{{ CashDevicesTable::sortUrl($column, $cashDevicesSort, $cashDevicesSortDir, $selectedSaleType, $selectedImeiTypeId) }}" class="text-gray-900 hover:text-blue-700 underline-offset-2 hover:underline">
                                {{ $label }}{{ CashDevicesTable::sortIndicator($column, $cashDevicesSort, $cashDevicesSortDir) }}
                            </a>
                        </th>
                    @endforeach
                    <th class="px-3 py-2 text-left font-semibold border-b border-gray-300 cash-devices-cost-incl-zone cursor-pointer select-none" title="Click to show cost incl">
                        Cost incl
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse($cashDevices as $imei)
                    @php
                        $sellingPriceDisplay = $imei->selling_price !== null ? number_format($imei->selling_price) : '—';
                        $costInclDisplay = ImeiCostIncl::format($imei->cost_excl) ?? '—';
                    @endphp
                    <tr class="{{ ImeiInShopAgeHighlight::rowClasses($imei) }} border-t border-gray-200">
                        <td class="px-3 py-2 whitespace-nowrap">
                            <a href="{{ route('imeis.edit', $imei).'?return_query='.rawurlencode($cashDevicesReturnQuery) }}" class="text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ $imei->date_in?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $imei->make !== '' ? $imei->make : '—' }}</td>
                        <td class="px-3 py-2">{{ $imei->model !== '' ? $imei->model : '—' }}</td>
                        <td class="px-3 py-2 max-w-xs truncate" title="{{ $imei->ref }}">{{ $imei->ref !== '' ? $imei->ref : '—' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap font-mono text-xs">{{ $imei->imei !== '' ? $imei->imei : '—' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ $sellingPriceDisplay }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap cash-devices-cost-incl-zone cursor-pointer select-none text-gray-500" title="Click to show cost incl">
                            <span class="cash-devices-cost-incl-mask">••••</span>
                            <span class="cash-devices-cost-incl-value hidden text-gray-900">{{ $costInclDisplay }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-8 text-center text-gray-500">No cash device records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('cash-devices-table');
    if (! table) {
        return;
    }

    const costInclZones = table.querySelectorAll('.cash-devices-cost-incl-zone');
    const masks = table.querySelectorAll('.cash-devices-cost-incl-mask');
    const values = table.querySelectorAll('.cash-devices-cost-incl-value');

    function revealCostIncl() {
        table.classList.add('cash-devices-cost-incl-revealed');
        masks.forEach(function (mask) {
            mask.classList.add('hidden');
        });
        values.forEach(function (value) {
            value.classList.remove('hidden');
        });
        costInclZones.forEach(function (zone) {
            zone.classList.remove('text-gray-500');
        });
    }

    function hideCostIncl() {
        table.classList.remove('cash-devices-cost-incl-revealed');
        masks.forEach(function (mask) {
            mask.classList.remove('hidden');
        });
        values.forEach(function (value) {
            value.classList.add('hidden');
        });
        costInclZones.forEach(function (zone) {
            zone.classList.add('text-gray-500');
        });
    }

    costInclZones.forEach(function (zone) {
        zone.addEventListener('click', function (event) {
            event.stopPropagation();
            revealCostIncl();
        });
    });

    document.addEventListener('click', function () {
        if (table.classList.contains('cash-devices-cost-incl-revealed')) {
            hideCostIncl();
        }
    });
});
</script>
@endsection
