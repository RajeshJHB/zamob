<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.favicon')
    <title>Repair — {{ $note->formattedNoteNumber() }}</title>
    @vite(['resources/css/app.css'])
    <style>
        .receipt-block {
            max-width: 32rem;
            margin-left: auto;
            margin-right: auto;
            text-align: center;
        }
        .receipt-line {
            margin-bottom: 0.35rem;
            text-align: center;
            word-break: break-word;
        }
        .receipt-label {
            font-weight: 600;
            display: inline;
        }
        .receipt-logo {
            display: block;
            margin-left: auto;
            margin-right: auto;
            max-width: 100%;
            height: auto;
            max-height: 8rem;
            margin-bottom: 0.75rem;
            object-fit: contain;
        }
        .receipt-tagline {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
        }
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12pt; }
            .receipt-logo { max-height: 7rem; }
        }
    </style>
</head>
<body class="bg-white p-6 text-gray-900">
    <div class="no-print mb-4 flex flex-wrap items-center gap-2">
        <button type="button" onclick="window.print();" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded text-sm">
            Print
        </button>
        <a href="{{ route('contacts.show', ['contact' => $contact, 'note' => $note->id]) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
            Back to contact
        </a>
    </div>

    <div class="receipt-block border border-gray-300 rounded-lg p-6 shadow-sm">
        <img src="{{ route('imeis.receipt.logo') }}" alt="" class="receipt-logo" width="480" height="160">
        <p class="receipt-tagline">Vodacom by zaMobile</p>

        <div class="receipt-line">
            <span class="receipt-label">Date:</span>
            {{ $note->noted_at->format('Y-m-d H:i') }}
        </div>
        <div class="receipt-line">
            <span class="receipt-label">Name:</span>
            {{ $contact->first_name !== '' ? $contact->first_name : '—' }}
        </div>
        <div class="receipt-line">
            <span class="receipt-label">Surname:</span>
            {{ $contact->surname !== '' ? $contact->surname : '—' }}
        </div>
        <div class="receipt-line">
            <span class="receipt-label">Reference Number:</span>
            {{ $note->formattedNoteNumber() }}
        </div>
        <div class="receipt-line">
            <span class="receipt-label">IMEI:</span>
            {{ $fields['imei'] !== '' ? $fields['imei'] : '—' }}
        </div>
        <div class="receipt-line">
            <span class="receipt-label">Make:</span>
            {{ $fields['make'] !== '' ? $fields['make'] : '—' }}
        </div>
        <div class="receipt-line">
            <span class="receipt-label">Model:</span>
            {{ $fields['model'] !== '' ? $fields['model'] : '—' }}
        </div>
        <div class="receipt-line">
            <span class="receipt-label">Problem:</span>
            {{ $fields['problem'] !== '' ? $fields['problem'] : '—' }}
        </div>
        <div class="receipt-line">
            <span class="receipt-label">Price:</span>
            {{ $fields['price'] !== '' ? $fields['price'] : '—' }}
        </div>

        <div class="mt-6 border-t border-gray-200 pt-3 text-center text-sm text-gray-600 space-y-1">
            <p>(011) 787-6524</p>
            <p>www.myVodacom.co.za</p>
            <p>www.zaMobile.co.za</p>
        </div>
    </div>
</body>
</html>
