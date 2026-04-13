@extends('layouts.app')

@section('title', $sectionLabel.' – IMEI Settings')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <div class="mb-4">
            <a href="{{ route('settings.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">← Back to IMEI Settings</a>
        </div>
        <h1 class="text-2xl font-bold mb-2">{{ $sectionLabel }}</h1>
        <p class="text-gray-600 text-sm">This section is not configured yet. Details will be added when you define how it should work.</p>
    </div>
</div>
@endsection
