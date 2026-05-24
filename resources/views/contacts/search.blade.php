@extends('layouts.app')

@section('title', 'Contacts')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <div class="grid grid-cols-1 sm:grid-cols-3 items-center gap-3 mb-6">
            <h1 class="text-2xl font-bold sm:justify-self-start">Contacts</h1>
            <a href="{{ route('contacts.create') }}" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2.5 px-5 rounded-md text-base inline-block text-center shadow-sm ring-2 ring-blue-200 justify-self-center sm:justify-self-center">New Contact</a>
            <div aria-hidden="true" class="hidden sm:block"></div>
        </div>

        @if(session('message'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">{{ session('message') }}</div>
        @endif

        <form method="GET" action="{{ route('contacts.index') }}" class="flex flex-wrap items-end gap-3 mb-6 pb-6 border-b border-gray-200">
            <div class="flex-1 min-w-[12rem]">
                <label for="q" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="q" id="q" value="{{ $term }}" placeholder="Phone, name, company, email, address…" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-md" autofocus>
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Search contacts</button>
        </form>

        @if($listingAll ?? false)
            <p class="text-sm text-gray-600 mb-4">Showing the newest contacts (up to <strong>{{ $browseListLimit ?? \App\Support\BrowseListLimit::limit() }}</strong> records).</p>
        @elseif($term !== '')
            <p class="text-sm text-gray-600 mb-4">Search: <strong>{{ $term }}</strong></p>
        @endif

        @if(($listingAll ?? false) && $contacts->isEmpty())
            <p class="text-gray-600 mb-4">No contacts yet. <a href="{{ route('contacts.create') }}" class="text-blue-600 hover:text-blue-800 font-medium">Create the first contact</a>.</p>
        @endif

        @if($showCreatePrompt ?? false)
            <div class="mb-6 bg-amber-50 border border-amber-200 text-amber-900 px-4 py-4 rounded">
                <p class="font-medium mb-2">No contact found.</p>
                <p class="text-sm mb-3">Create a new contact with these details?</p>
                <a href="{{ route('contacts.create', ['telephone_1' => $term]) }}" class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold rounded">
                    Create new contact
                </a>
            </div>
        @endif

        @if($contacts->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-300 text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Select</th>
                            <th class="px-3 py-2 text-left font-semibold">Company</th>
                            <th class="px-3 py-2 text-left font-semibold">First name</th>
                            <th class="px-3 py-2 text-left font-semibold">Surname</th>
                            <th class="px-3 py-2 text-left font-semibold">Tel 1</th>
                            <th class="px-3 py-2 text-left font-semibold">Tel 2</th>
                            <th class="px-3 py-2 text-left font-semibold">Email</th>
                            <th class="px-3 py-2 text-left font-semibold">Address</th>
                            <th class="px-3 py-2 text-left font-semibold">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contacts as $row)
                            <tr class="hover:bg-gray-50 border-t border-gray-200">
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <a href="{{ route('contacts.show', $row) }}" class="text-blue-600 hover:text-blue-800 font-medium">Select</a>
                                </td>
                                <td class="px-3 py-2">{{ $row->company_name ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row->first_name ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row->surname ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row->telephone_1 ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row->telephone_2 ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row->email_address ?: '—' }}</td>
                                <td class="px-3 py-2 max-w-xs truncate" title="{{ $row->physical_address }}">{{ $row->physical_address ?: '—' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $row->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
