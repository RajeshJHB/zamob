@extends('layouts.app')

@section('title', $contact->displayName())

@section('content')
@php
    use App\Support\ContactPermissions;
    $user = auth()->user();
@endphp
<div class="max-w-4xl mx-auto space-y-6">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h1 class="text-2xl font-bold">{{ $contact->displayName() }}</h1>
            <a href="{{ route('contacts.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">← Contacts</a>
        </div>

        @if(session('message'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('message') }}</div>
        @endif

        @include('contacts.partials.fields-display', ['contact' => $contact])

        <div class="flex flex-wrap gap-3 mt-6 pt-4 border-t border-gray-200">
            <a href="{{ route('contacts.edit', $contact) }}" class="inline-flex items-center px-4 py-2 border border-gray-900 text-sm font-medium rounded-md text-gray-900 bg-white hover:bg-gray-50">Edit contact</a>
            @if($canDeleteContact)
                <form method="POST" action="{{ route('contacts.destroy', $contact) }}" class="inline" onsubmit="return confirm('Delete this contact and all service notes?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50">Delete contact</button>
                </form>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h2 class="text-xl font-bold">Service notes</h2>
            <a href="{{ route('contacts.service-notes.create', $contact) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">New service note</a>
        </div>

        @forelse($contact->serviceNotes as $note)
            @php
                $highlighted = $highlightNoteId === $note->id;
                $canEditNote = ContactPermissions::canEditServiceNote($user, $note);
                $canDeleteNote = ContactPermissions::canDeleteServiceNote($user, $note);
            @endphp
            <div id="service-note-{{ $note->id }}" @class([
                'border rounded-lg p-4 mb-4',
                'border-blue-400 bg-blue-50 ring-1 ring-blue-200' => $highlighted,
                'border-gray-200' => ! $highlighted,
            ])>
                <div class="flex flex-wrap items-center gap-2 mb-1 text-sm text-gray-600">
                    @if($note->noteType)
                        <span class="font-medium text-gray-800">{{ $note->noteType->name }}</span>
                        <span class="text-gray-400">·</span>
                    @endif
                    <span class="font-mono font-semibold text-gray-800">{{ $note->formattedNoteNumber() }}</span>
                    <span class="text-gray-400">·</span>
                    <span @class([
                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                        'bg-green-100 text-green-800' => $note->status === \App\Models\ServiceNote::STATUS_OPEN,
                        'bg-gray-200 text-gray-700' => $note->isClosed(),
                    ])>{{ ucfirst($note->status) }}</span>
                    <span class="text-gray-400">·</span>
                    <span class="text-gray-500 whitespace-nowrap">{{ $note->noted_at->format('Y-m-d H:i') }}</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $note->heading }}</h3>
                <p class="text-sm text-gray-700 whitespace-pre-wrap mb-3">{{ $note->body }}</p>
                @if($note->staff)
                    <p class="text-xs text-gray-500 mb-3">Staff: {{ $note->staff }}</p>
                @endif
                @if($note->hasAttachment())
                    <p class="text-sm mb-3">
                        <a href="{{ route('service-notes.attachment', $note) }}" class="text-blue-600 hover:text-blue-800 font-medium" target="_blank" rel="noopener noreferrer">
                            Download: {{ $note->attachment_original_name }}
                        </a>
                    </p>
                @endif
                <div class="flex flex-wrap gap-3 text-sm">
                    <a href="{{ route('service-notes.print', $note) }}" class="text-gray-700 hover:text-gray-900 font-medium" target="_blank" rel="noopener noreferrer">Print</a>
                    @if($canEditNote)
                        <a href="{{ route('service-notes.edit', $note) }}" class="text-blue-600 hover:text-blue-800 font-medium">Edit</a>
                    @endif
                    @if($canDeleteNote)
                        <form method="POST" action="{{ route('service-notes.destroy', $note) }}" class="inline" onsubmit="return confirm('Delete this service note?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Delete</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-gray-600 text-sm">No service notes yet. Create the first one above.</p>
        @endforelse
    </div>
</div>
@endsection
