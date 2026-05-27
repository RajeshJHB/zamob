<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>Print – Notes</title>
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
        <a href="{{ route('notes.index', request()->except('auto')) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
            Back to notes
        </a>
    </div>

    <h1 class="text-2xl font-bold mb-2">Notes – All results ({{ $notes->count() }} rows)</h1>
    <p class="text-sm text-gray-600 mb-4 no-print">Use the Print button above, or your browser's print (Ctrl+P / Cmd+P).</p>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-300">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-2 py-1 text-left text-xs font-semibold text-gray-700 border-b border-gray-300 whitespace-nowrap">Start Time</th>
                    <th class="px-2 py-1 text-left text-xs font-semibold text-gray-700 border-b border-gray-300 whitespace-nowrap">Note Type</th>
                    <th class="px-2 py-1 text-left text-xs font-semibold text-gray-700 border-b border-gray-300 whitespace-nowrap">Status</th>
                    <th class="px-2 py-1 text-left text-xs font-semibold text-gray-700 border-b border-gray-300 whitespace-nowrap">Heading</th>
                    <th class="px-2 py-1 text-left text-xs font-semibold text-gray-700 border-b border-gray-300">Note</th>
                    <th class="px-2 py-1 text-left text-xs font-semibold text-gray-700 border-b border-gray-300 whitespace-nowrap">Attached file name</th>
                    <th class="px-2 py-1 text-left text-xs font-semibold text-gray-700 border-b border-gray-300 whitespace-nowrap">Last Time</th>
                </tr>
            </thead>
            <tbody>
                @forelse($notes as $note)
                    <tr class="border-b border-gray-200 align-top">
                        <td class="px-2 py-1 text-sm text-gray-700 whitespace-nowrap">{{ $note->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-2 py-1 text-sm text-gray-700 whitespace-nowrap">{{ $note->noteType?->name ?? '—' }}</td>
                        <td class="px-2 py-1 text-sm text-gray-700 whitespace-nowrap">{{ ucfirst($note->status) }}</td>
                        <td class="px-2 py-1 text-sm text-gray-700">{{ $note->heading }}</td>
                        <td class="px-2 py-1 text-sm text-gray-700 whitespace-pre-wrap">{{ $note->body }}</td>
                        <td class="px-2 py-1 text-sm text-gray-700">{{ $note->attachment_original_name ?: '—' }}</td>
                        <td class="px-2 py-1 text-sm text-gray-700 whitespace-nowrap">{{ $note->updated_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-2 py-6 text-center text-gray-500">No service notes matched your search.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.location.search.includes('auto=1')) {
                window.print();
            }
        });
    </script>
</body>
</html>
