<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $note->formattedNoteNumber() }} — {{ $note->heading }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 p-8 max-w-3xl mx-auto">
    <div class="no-print mb-6 flex gap-3">
        <button type="button" onclick="window.print()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
            Print
        </button>
        <a href="{{ route('contacts.show', ['contact' => $contact, 'note' => $note->id]) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded text-sm inline-block">
            Back to contact
        </a>
    </div>

    <header class="border-b border-gray-300 pb-4 mb-6">
        <p class="text-sm text-gray-600 uppercase tracking-wide">{{ $note->noteType?->name ?? 'Service note' }}</p>
        <h1 class="text-2xl font-bold mt-1">{{ $note->heading }}</h1>
        <p class="text-sm text-gray-700 mt-2">
            <span class="font-semibold">{{ $note->formattedNoteNumber() }}</span>
            · {{ $note->noted_at->format('Y-m-d H:i') }}
            · {{ ucfirst($note->status) }}
        </p>
    </header>

    <section class="mb-8">
        <h2 class="text-sm font-bold text-gray-500 uppercase mb-2">Contact</h2>
        <p class="text-lg font-semibold">{{ $contact->displayName() }}</p>
        @include('contacts.partials.fields-display', ['contact' => $contact])
    </section>

    <section class="mb-8">
        <h2 class="text-sm font-bold text-gray-500 uppercase mb-2">Note</h2>
        <div class="whitespace-pre-wrap text-gray-900 leading-relaxed">{{ $note->body }}</div>
    </section>

    @if($note->hasAttachment())
        <section>
            <h2 class="text-sm font-bold text-gray-500 uppercase mb-2">Attachment</h2>
            <p class="text-sm text-gray-700">{{ $note->attachment_original_name }}</p>
        </section>
    @endif
</body>
</html>
