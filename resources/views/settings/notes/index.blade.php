@extends('layouts.app')

@section('title', 'Note Settings')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h1 class="text-2xl font-bold">Note Settings</h1>
            <a href="{{ route('dashboard') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-sm">Home</a>
        </div>
        <p class="text-gray-600 text-sm mb-6">Manage reference data used on service notes.</p>
        <ul class="space-y-2 text-sm">
            <li>
                <a href="{{ route('settings.note-types.index') }}" class="block text-blue-600 hover:text-blue-800 font-medium py-1">
                    Note types
                </a>
            </li>
        </ul>
    </div>
</div>
@endsection
