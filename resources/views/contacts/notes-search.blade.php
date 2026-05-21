@extends('layouts.app')

@section('title', 'Search service notes')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <h1 class="text-2xl font-bold">Service note search</h1>
            <a href="{{ route('contacts.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">← Back to Contacts</a>
        </div>

        <p class="text-sm text-gray-600 mb-4">Search: <strong>{{ $term }}</strong></p>

        @forelse($notes as $note)
            <div class="border border-gray-200 rounded-lg p-4 mb-4 hover:bg-gray-50">
                <div class="flex flex-wrap items-center gap-2 mb-1 text-sm text-gray-600">
                    @if($note->noteType)
                        <span class="font-medium text-gray-800">{{ $note->noteType->name }}</span>
                        <span class="text-gray-400">·</span>
                    @endif
                    <span class="font-mono font-semibold text-gray-800">{{ $note->formattedNoteNumber() }}</span>
                    <span class="text-gray-400">·</span>
                    <span class="text-gray-500 whitespace-nowrap">{{ $note->noted_at->format('Y-m-d H:i') }}</span>
                </div>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">{{ $note->heading }}</h2>
                <p class="text-sm text-gray-700 whitespace-pre-wrap mb-3">{{ Str::limit($note->body, 300) }}</p>
                <p class="text-sm">
                    <span class="text-gray-500">Contact:</span>
                    <a href="{{ route('contacts.show', ['contact' => $note->contact, 'note' => $note->id]) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                        {{ $note->contact->displayName() }}
                    </a>
                </p>
            </div>
        @empty
            <p class="text-gray-600">No service notes matched your search.</p>
        @endforelse
    </div>
</div>
@endsection
