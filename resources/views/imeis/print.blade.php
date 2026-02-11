<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print – IMEI's</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11px; }
            table { page-break-inside: auto; }
            tr { page-break-inside: avoid; page-break-after: auto; }
        }
    </style>
</head>
<body class="bg-white p-4">
    <div class="no-print mb-4 flex flex-wrap items-center gap-2">
        <button type="button" onclick="window.print();" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Print
        </button>
        <a href="{{ route('imeis.index', request()->query()) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            Back to results
        </a>
    </div>

    <h1 class="text-2xl font-bold mb-2">IMEI's – All results ({{ $imeis->count() }} rows)</h1>
    <p class="text-sm text-gray-600 mb-4 no-print">Use the Print button above, or your browser's print (Ctrl+P / Cmd+P).</p>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-300">
            <thead>
                <tr class="bg-gray-100">
                    @foreach($columns as $col)
                        <th class="px-2 py-1 text-left text-xs font-semibold text-gray-700 border-b border-gray-300">{{ $columnLabels[$col] ?? $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($imeis as $row)
                    <tr class="border-b border-gray-200">
                        @foreach($columns as $col)
                            <td class="px-2 py-1 text-sm text-gray-700">
                                @if($col === 'date_in' || $col === 'date_updated')
                                    {{ $row->$col?->format('Y-m-d H:i') ?? '—' }}
                                @elseif($col === 'notes')
                                    {{ $row->$col ?? '—' }}
                                @else
                                    {{ $row->$col ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-2 py-6 text-center text-gray-500">No IMEI records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.location.search.includes('auto=1')) {
                window.print();
            }
        });
    </script>
</body>
</html>
