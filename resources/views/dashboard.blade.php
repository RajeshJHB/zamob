@extends('layouts.app')

@section('title', 'ZAMOBILE HOME')

@section('content')
@php
    use App\Support\CashDevicesTable;

    $sortableHeaders = [
        'date_in' => 'Date In',
        'make' => 'Make',
        'model' => 'Model',
        'imei' => 'IMEI',
        'selling_price' => 'Selling Price',
    ];
@endphp
<div class="space-y-8">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h1 class="text-3xl font-bold">ZAMOBILE HOME</h1>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h2 class="text-2xl font-bold mb-4">Cash Devices</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300 text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold border-b border-gray-300">View</th>
                        @foreach($sortableHeaders as $column => $label)
                            <th class="px-3 py-2 text-left font-semibold border-b border-gray-300">
                                <a href="{{ CashDevicesTable::sortUrl($column, $cashDevicesSort, $cashDevicesSortDir) }}" class="text-gray-900 hover:text-blue-700 underline-offset-2 hover:underline">
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
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-xs">{{ $imei->imei !== '' ? $imei->imei : '—' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                {{ $imei->selling_price !== null ? number_format($imei->selling_price) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-gray-500">No cash device records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
