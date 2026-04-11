<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Device Receipt – {{ $imei->imei }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .receipt-block {
            max-width: 32rem;
            margin-left: auto;
            margin-right: auto;
            text-align: center;
        }
        .receipt-line { margin-bottom: 0.35rem; text-align: center; }
        .receipt-label { font-weight: 600; display: inline; }
        .receipt-logo {
            display: block;
            margin-left: auto;
            margin-right: auto;
            max-width: 100%;
            height: auto;
            max-height: 8rem;
            margin-bottom: 1rem;
            object-fit: contain;
        }
        .receipt-block h1 { text-align: center; }
        .receipt-line-model .receipt-label,
        .receipt-model-value,
        .receipt-line-imei .receipt-label,
        .receipt-imei-value {
            max-width: 100%;
            word-break: break-word;
        }
        .receipt-line-model .receipt-label {
            font-size: 2em;
            line-height: 1.35;
            font-weight: 400;
            display: inline-block;
            vertical-align: middle;
        }
        .receipt-model-value {
            font-size: 2em;
            line-height: 1.35;
            font-weight: 400;
            display: inline-block;
            vertical-align: middle;
        }
        .receipt-line-imei .receipt-label {
            font-size: 2em;
            line-height: 1.35;
            font-weight: 700;
            display: inline-block;
            vertical-align: middle;
        }
        .receipt-imei-value {
            font-size: 2em;
            font-weight: 700;
            line-height: 1.35;
            display: inline-block;
            vertical-align: middle;
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
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('imeis.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
            Back
        </a>
    </div>

    <div class="receipt-block border border-gray-300 rounded-lg p-6 shadow-sm">
        <img src="{{ route('imeis.receipt.logo') }}" alt="" class="receipt-logo" width="480" height="160">
        <h1 class="text-xl font-bold mb-4 border-b border-gray-300 pb-2 text-center">Device Receipt</h1>

        <div class="receipt-line">
            <span class="receipt-label">Date In:</span>
            {{ $imei->date_in?->format('Y-m-d H:i:s') ?? '—' }}
        </div>
        <div class="receipt-line">
            <span class="receipt-label">Date Printed:</span>
            {{ now()->format('n/j/Y H:i:s') }}
        </div>
        <div class="receipt-line">
            <span class="receipt-label">Make:</span>
            {{ $imei->make !== '' ? $imei->make : '—' }}
        </div>
        <div class="receipt-line receipt-line-model">
            <span class="receipt-label">Model:</span>
            <span class="receipt-model-value">{{ $imei->model !== '' ? $imei->model : '—' }}</span>
        </div>
        <div class="receipt-line">
            <span class="receipt-label">Serial Number:</span>
            {{ $imei->sn !== '' ? $imei->sn : '—' }}
        </div>
        <div class="receipt-line receipt-line-imei">
            <span class="receipt-label">Imei:</span>
            <span class="receipt-imei-value">{{ $imei->imei ?? '—' }}</span>
        </div>
        <div class="receipt-line">
            <span class="receipt-label">Phone Number:</span>
            {{ $imei->phonenumber !== '' ? $imei->phonenumber : '' }}
        </div>
        <div class="receipt-line mt-3">
            <span class="receipt-label">Notes:</span>
        </div>
        <div class="receipt-line whitespace-pre-wrap text-sm text-center">{{ $imei->notes !== '' && $imei->notes !== null ? $imei->notes : '' }}</div>

        <div class="mt-6 border-t border-gray-200 pt-3 text-center text-sm text-gray-600 space-y-1">
            <p>(011) 787 6524</p>
            <p>www.zamobile.co.za</p>
            <p>Terms and Conditions Apply</p>
        </div>
    </div>
</body>
</html>
