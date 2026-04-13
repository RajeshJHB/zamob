@extends('layouts.app')

@section('title', 'IMEI Settings')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h1 class="text-2xl font-bold mb-4">IMEI Settings</h1>
        <p class="text-gray-600 text-sm mb-6">Manage reference data used across IMEI records. Profile and password are available from your account menu (top right).</p>
        <ul class="space-y-2 text-sm">
            @foreach($sections as $key => $label)
                <li>
                    <a href="{{ match ($key) {
                        'makes' => route('settings.makes.index'),
                        'models' => route('settings.models.index'),
                        'locations' => route('settings.locations.index'),
                        'types' => route('settings.types.index'),
                        'status' => route('settings.status.index'),
                        default => route('settings.index'),
                    } }}" class="block text-blue-600 hover:text-blue-800 font-medium py-1">
                        {{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection
