@extends('layouts.app')

@section('title', 'Home')

@section('content')
@php
    use App\Support\CashDevicesTable;

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

    <div class="overflow-x-auto">
        <table class="min-w-full border border-gray-300 text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold border-b border-gray-300">View</th>
                    @foreach($sortableHeaders as $column => $label)
                        <th class="px-3 py-2 text-left font-semibold border-b border-gray-300">
                            <a href="{{ CashDevicesTable::sortUrl($column, $cashDevicesSort, $cashDevicesSortDir, $selectedSaleType) }}" class="text-gray-900 hover:text-blue-700 underline-offset-2 hover:underline">
                                {{ $label }}{{ CashDevicesTable::sortIndicator($column, $cashDevicesSort, $cashDevicesSortDir) }}
                            </a>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($cashDevices as $imei)
                    <tr class="hover:bg-gray-50 border-t border-gray-200">
                        <td class="px-3 py-2 whitespace-nowrap">
                            <a href="{{ route('imeis.edit', $imei).'?return_query='.rawurlencode($cashDevicesReturnQuery) }}" class="text-blue-600 hover:text-blue-800 font-medium">View</a>
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ $imei->date_in?->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $imei->make !== '' ? $imei->make : '—' }}</td>
                        <td class="px-3 py-2">{{ $imei->model !== '' ? $imei->model : '—' }}</td>
                        <td class="px-3 py-2 max-w-xs truncate" title="{{ $imei->ref }}">{{ $imei->ref !== '' ? $imei->ref : '—' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap font-mono text-xs">{{ $imei->imei !== '' ? $imei->imei : '—' }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{ $imei->selling_price !== null ? number_format($imei->selling_price) : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-8 text-center text-gray-500">No cash device records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
