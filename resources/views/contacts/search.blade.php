@extends('layouts.app')

@section('title', 'Contact search')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <div class="grid grid-cols-1 sm:grid-cols-3 items-center gap-3 mb-6">
            <a href="{{ route('contacts.index') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium sm:justify-self-start">← Back to Contacts</a>
            <div class="flex flex-wrap items-center justify-center gap-3 sm:justify-self-center">
                <h1 class="text-2xl font-bold">Contact search</h1>
                <a href="{{ route('contacts.create') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded text-sm inline-block">New contact</a>
            </div>
            <div class="hidden sm:block" aria-hidden="true"></div>
        </div>

        @if($listingAll ?? false)
            <p class="text-sm text-gray-600 mb-4">Showing the newest contacts (up to <strong>{{ $browseListLimit ?? \App\Support\BrowseListLimit::limit() }}</strong> records).</p>
        @else
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
                            <th class="px-3 py-2 text-left font-semibold">Company</th>
                            <th class="px-3 py-2 text-left font-semibold">First name</th>
                            <th class="px-3 py-2 text-left font-semibold">Surname</th>
                            <th class="px-3 py-2 text-left font-semibold">Tel 1</th>
                            <th class="px-3 py-2 text-left font-semibold">Tel 2</th>
                            <th class="px-3 py-2 text-left font-semibold">Email</th>
                            <th class="px-3 py-2 text-left font-semibold">Address</th>
                            <th class="px-3 py-2 text-left font-semibold">Created</th>
                            <th class="px-3 py-2 text-left font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contacts as $row)
                            <tr class="hover:bg-gray-50 border-t border-gray-200">
                                <td class="px-3 py-2">{{ $row->company_name ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row->first_name ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row->surname ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row->telephone_1 ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row->telephone_2 ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row->email_address ?: '—' }}</td>
                                <td class="px-3 py-2 max-w-xs truncate" title="{{ $row->physical_address }}">{{ $row->physical_address ?: '—' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $row->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <a href="{{ route('contacts.show', $row) }}" class="text-blue-600 hover:text-blue-800 font-medium">Select</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
