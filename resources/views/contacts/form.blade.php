@extends('layouts.app')

@section('title', $contact ? 'Edit contact' : 'New contact')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <div class="mb-4">
            <a href="{{ $contact ? route('contacts.show', $contact) : route('contacts.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">← Back</a>
        </div>
        <h1 class="text-2xl font-bold mb-6">{{ $contact ? 'Edit contact' : 'New contact' }}</h1>

        <form method="POST" action="{{ $contact ? route('contacts.update', $contact) : route('contacts.store') }}" class="space-y-4">
            @csrf
            @if($contact)
                @method('PUT')
            @endif

            @include('contacts.partials.form-fields', ['contact' => $contact, 'contactsForRelated' => $contactsForRelated, 'prefill' => $prefill])

            <div class="flex flex-wrap gap-3 pt-4">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Save contact</button>
                <a href="{{ $contact ? route('contacts.show', $contact) : route('contacts.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded inline-block">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
