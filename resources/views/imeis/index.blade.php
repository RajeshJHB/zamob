@extends('layouts.app')

@section('contentWidth', 'full')

@section('title', 'Find IMEI\'s')

@section('content')
@php
    use Illuminate\Support\Str;

    $listReturnQuery = http_build_query(array_filter(array_merge(
        $filterParams ?? [],
        request()->only(['page']),
    ), fn (mixed $value): bool => $value !== null && $value !== ''));
    $imeiBrowseTextDisplayLimit = 150;
@endphp
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">IMEI's</h1>
        <div class="flex items-center gap-2">
            @if($canBulkChangeStatus ?? false)
                <div class="relative" id="imei-advanced-menu-container">
                    <button id="imei-advanced-menu-button" type="button" class="bg-indigo-600 hover:bg-indigo-800 text-white font-bold py-1 px-3 rounded text-sm flex items-center gap-1">
                        Advanced
                        <svg id="imei-advanced-menu-arrow" class="h-3 w-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="imei-advanced-menu-dropdown"
                         class="absolute right-0 mt-1 w-52 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200 hidden">
                        <button type="button" id="imei-bulk-status-open" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Bulk change Status
                        </button>
                    </div>
                </div>
            @endif
            <a href="{{ route('imeis.print', $filterParams ?? []) }}" target="_blank" class="bg-gray-600 hover:bg-gray-800 text-white font-bold py-1 px-3 rounded text-sm">
                Print all results
            </a>
            <a href="{{ route('imeis.filter', $filterParams ?? []) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-1 px-3 rounded text-sm">
                Change filter
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

    <div class="-mx-6 overflow-x-auto overscroll-x-contain">
        <table class="w-max min-w-full border-collapse bg-white border border-gray-300">
            <thead>
                <tr class="bg-gray-100">
                    <th class="whitespace-nowrap border border-gray-300 px-1.5 py-1.5 text-left text-xs font-semibold text-gray-700">Print</th>
                    <th class="whitespace-nowrap border border-gray-300 px-1.5 py-1.5 text-left text-xs font-semibold text-gray-700">View</th>
                    @foreach($columns as $col)
                        <th class="whitespace-nowrap border border-gray-300 px-3 py-2 text-left text-xs font-semibold text-gray-700">{{ $columnLabels[$col] ?? $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($imeis as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap border border-gray-300 px-1.5 py-1.5 text-xs">
                            <a href="{{ route('imeis.receipt', $row) }}" target="_blank" rel="noopener noreferrer" class="text-blue-700 hover:text-blue-900 font-medium underline">Print</a>
                        </td>
                        <td class="whitespace-nowrap border border-gray-300 px-1.5 py-1.5 text-xs">
                            <a href="{{ route('imeis.edit', $row).($listReturnQuery !== '' ? '?return_query='.rawurlencode($listReturnQuery) : '') }}" class="text-blue-700 hover:text-blue-900 font-medium underline">View</a>
                        </td>
                        @foreach($columns as $col)
                            <td @class([
                                'border border-gray-300 px-3 py-2 text-sm text-gray-700',
                                'whitespace-nowrap' => ! in_array($col, ['notes', 'ref'], true),
                            ])>
                                @if($col === 'date_in' || $col === 'date_updated')
                                    {{ $row->$col?->format('Y-m-d H:i') ?? '—' }}
                                @elseif($col === 'cost_incl')
                                    {{ \App\Support\ImeiCostIncl::format($row->cost_excl) ?? '—' }}
                                @elseif($col === 'notes' || $col === 'ref')
                                    @php $cellText = (string) ($row->$col ?? ''); @endphp
                                    @if($cellText !== '')
                                        <span class="inline-block max-w-xs" title="{{ $cellText }}">{{ Str::limit($cellText, $imeiBrowseTextDisplayLimit) }}</span>
                                    @else
                                        —
                                    @endif
                                @else
                                    {{ $row->$col ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 2 + count($columns) }}" class="border border-gray-300 px-3 py-8 text-center text-gray-500">No IMEI records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($imeis->hasPages())
        <div class="mt-4">
            {{ $imeis->links() }}
        </div>
    @endif
</div>

@if($canBulkChangeStatus ?? false)
<div
    id="imei-bulk-status-modal"
    class="fixed inset-0 z-50 hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="imei-bulk-status-title"
>
    <div class="absolute inset-0 bg-black/40" id="imei-bulk-status-backdrop"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg border border-gray-200">
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-gray-200">
                <h2 id="imei-bulk-status-title" class="text-xl font-bold text-gray-900">Bulk change Status</h2>
                <button type="button" id="imei-bulk-status-close" class="text-gray-500 hover:text-gray-800 text-2xl leading-none" aria-label="Close">&times;</button>
            </div>
            <form method="POST" action="{{ route('imeis.bulk-status') }}" class="px-6 py-4 space-y-4">
                @csrf
                @foreach($filterParams ?? [] as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $item)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <p class="text-sm text-gray-700">
                    Change status for <strong>{{ number_format($bulkStatusCount) }}</strong> record(s) in this search
                    from <strong>{{ $statusFilterValue }}</strong> to:
                </p>

                <div>
                    <label for="status_to" class="block text-sm font-medium text-gray-700 mb-1">New status</label>
                    <select name="status_to" id="status_to" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">Choose status…</option>
                        @foreach($statusOptions as $statusOption)
                            @if($statusOption !== $statusFilterValue)
                                <option value="{{ $statusOption }}" @selected(old('status_to') === $statusOption)>{{ $statusOption }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" id="imei-bulk-status-cancel" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded text-sm">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        id="imei-bulk-status-submit"
                        class="bg-indigo-600 hover:bg-indigo-800 text-white font-bold py-2 px-4 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        @disabled($bulkStatusCount === 0)
                    >
                        Update {{ number_format($bulkStatusCount) }} record(s)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const advancedButton = document.getElementById('imei-advanced-menu-button');
    const advancedDropdown = document.getElementById('imei-advanced-menu-dropdown');
    const advancedArrow = document.getElementById('imei-advanced-menu-arrow');
    const advancedContainer = document.getElementById('imei-advanced-menu-container');
    const bulkOpenButton = document.getElementById('imei-bulk-status-open');
    const modal = document.getElementById('imei-bulk-status-modal');
    const backdrop = document.getElementById('imei-bulk-status-backdrop');
    const closeButton = document.getElementById('imei-bulk-status-close');
    const cancelButton = document.getElementById('imei-bulk-status-cancel');

    function closeAdvancedMenu() {
        if (! advancedDropdown) {
            return;
        }
        advancedDropdown.classList.add('hidden');
        if (advancedArrow) {
            advancedArrow.style.transform = 'rotate(0deg)';
        }
    }

    function openModal() {
        if (! modal) {
            return;
        }
        modal.classList.remove('hidden');
        closeAdvancedMenu();
    }

    function closeModal() {
        if (! modal) {
            return;
        }
        modal.classList.add('hidden');
    }

    if (advancedButton && advancedDropdown) {
        advancedButton.addEventListener('click', function (event) {
            event.stopPropagation();
            const isHidden = advancedDropdown.classList.contains('hidden');
            if (isHidden) {
                advancedDropdown.classList.remove('hidden');
                if (advancedArrow) {
                    advancedArrow.style.transform = 'rotate(180deg)';
                }
            } else {
                closeAdvancedMenu();
            }
        });

        document.addEventListener('click', function (event) {
            if (advancedContainer && ! advancedContainer.contains(event.target)) {
                closeAdvancedMenu();
            }
        });
    }

    if (bulkOpenButton) {
        bulkOpenButton.addEventListener('click', openModal);
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeModal);
    }

    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', closeModal);
    }

    @if($errors->has('status_to') || $errors->has('field_filter'))
        openModal();
    @endif
});
</script>
@endif
@endsection
