@extends('layouts.app')

@section('title', 'Contact Settings')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h1 class="text-2xl font-bold mb-4">Contact Settings</h1>
        <p class="text-gray-600 text-sm mb-6">Manage reference data used on contacts.</p>
        <ul class="space-y-2 text-sm">
            <li>
                <a href="{{ route('settings.contact-categories.index') }}" class="block text-blue-600 hover:text-blue-800 font-medium py-1">
                    Contact categories
                </a>
            </li>
        </ul>
    </div>
</div>
@endsection
