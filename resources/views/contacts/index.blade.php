@extends('layouts.app')

@section('title', 'Contacts')

@section('content')
<div class="max-w-3xl mx-auto space-y-8">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h1 class="text-2xl font-bold mb-2">Contacts</h1>
        <p class="text-sm text-gray-600 mb-6">Search by phone, name, company, email, or address. Search service notes across all contacts.</p>

        @if(session('message'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('message') }}</div>
        @endif

        <form method="GET" action="{{ route('contacts.search') }}" class="space-y-3 pb-6 border-b border-gray-200">
            <h2 class="text-lg font-semibold">Find contact</h2>
            <p class="text-sm text-gray-600">Searches all contact fields. Leave blank and search to list every contact (newest first).</p>
            <input type="text" name="q" value="{{ $contactQuery }}" placeholder="Phone, name, company, email, address…" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-md" autofocus>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Search contacts</button>
        </form>

        <form method="GET" action="{{ route('contacts.notes.search') }}" class="space-y-3">
            <h2 class="text-lg font-semibold">Search all service notes</h2>
            <p class="text-sm text-gray-600">Matches heading or note text. Leave blank and search to list all service notes (20 per page).</p>
            <input type="text" name="note_q" value="{{ $noteQuery }}" placeholder="Text in service notes…" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-md">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Search notes</button>
        </form>
    </div>
</div>
@endsection
