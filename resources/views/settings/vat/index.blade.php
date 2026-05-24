@extends('layouts.app')

@section('title', 'VAT Settings')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h1 class="text-2xl font-bold mb-4">VAT Settings</h1>

        <p class="text-gray-600 text-sm mb-6">
            VAT percentage is used to calculate <strong>Cost incl</strong> from <strong>Cost excl</strong> on IMEI records
            (Cost incl = Cost excl + VAT).
        </p>

        @if (session('message'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">
                {{ session('message') }}
            </div>
        @endif

        <form method="POST" action="{{ route('settings.vat.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="vat_percent" class="block text-sm font-medium text-gray-700 mb-1">VAT percentage</label>
                <div class="flex items-center gap-2 max-w-xs">
                    <input
                        type="number"
                        name="vat_percent"
                        id="vat_percent"
                        value="{{ old('vat_percent', $vatPercent) }}"
                        min="0"
                        max="100"
                        step="0.01"
                        required
                        class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full @error('vat_percent') border-red-500 @enderror"
                    >
                    <span class="text-gray-700 font-medium">%</span>
                </div>
                <p class="mt-1 text-xs text-gray-500">Enter a value from 0 to 100.</p>
                @error('vat_percent')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Save VAT
            </button>
        </form>
    </div>
</div>
@endsection
