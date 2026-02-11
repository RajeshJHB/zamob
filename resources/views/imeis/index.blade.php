@extends('layouts.app')

@section('title', 'Find IMEI\'s')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">IMEI's</h1>
        <div class="flex items-center gap-2">
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

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-300">
            <thead>
                <tr class="bg-gray-100">
                    @foreach($columns as $col)
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 border-b">{{ $columnLabels[$col] ?? $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($imeis as $row)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        @foreach($columns as $col)
                            <td class="px-3 py-2 text-sm text-gray-700">
                                @if($col === 'date_in' || $col === 'date_updated')
                                    {{ $row->$col?->format('Y-m-d H:i') ?? '—' }}
                                @elseif($col === 'notes')
                                    <span class="max-w-xs truncate block" title="{{ $row->$col }}">{{ $row->$col ?? '—' }}</span>
                                @else
                                    {{ $row->$col ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-3 py-8 text-center text-gray-500">No IMEI records found.</td>
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
@endsection
