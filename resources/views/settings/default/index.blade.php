@extends('layouts.app')

@section('title', 'Default Settings')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h1 class="text-2xl font-bold">Default Settings</h1>
            <a href="{{ route('dashboard') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-sm">Home</a>
        </div>

        <p class="text-gray-600 text-sm mb-6">
            Used when <strong>IMEI</strong> or <strong>Contacts</strong> search is run with no search text
            (and, for IMEI, no date range or custom sort). Only the newest records are shown, up to this limit.
        </p>

        @if (session('message'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">
                {{ session('message') }}
            </div>
        @endif

        <form method="POST" action="{{ route('settings.default.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="browse_list_limit" class="block text-sm font-medium text-gray-700 mb-1">Max records for blank browse</label>
                <input
                    type="number"
                    name="browse_list_limit"
                    id="browse_list_limit"
                    value="{{ old('browse_list_limit', $browseListLimit) }}"
                    min="{{ \App\Support\BrowseListLimit::MIN_LIMIT }}"
                    max="{{ \App\Support\BrowseListLimit::MAX_LIMIT }}"
                    step="1"
                    required
                    class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-xs @error('browse_list_limit') border-red-500 @enderror"
                >
                <p class="mt-1 text-xs text-gray-500">
                    Default is {{ \App\Support\BrowseListLimit::DEFAULT_LIMIT }}.
                    Allowed range: {{ \App\Support\BrowseListLimit::MIN_LIMIT }}&ndash;{{ \App\Support\BrowseListLimit::MAX_LIMIT }}.
                </p>
                @error('browse_list_limit')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Save defaults
            </button>
        </form>
    </div>
</div>
@endsection
