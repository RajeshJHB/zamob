@extends('layouts.app')

@section('title', 'Contacts')

@section('content')
@php
    use App\Support\ContactCategoryFilter;
    use App\Support\ContactsTable;

    $categorySelectValue = $selectedCategory === ContactCategoryFilter::ALL
        ? ContactCategoryFilter::ALL
        : (string) $selectedCategory;
@endphp
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

        <form method="GET" action="{{ route('contacts.index') }}" id="contacts-filter-form" class="flex flex-wrap items-end gap-3 mb-6 pb-6 border-b border-gray-200">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="dir" value="{{ $sortDir }}">
            <div class="min-w-[10rem]">
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category" id="category" onchange="this.form.submit()" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full bg-white">
                    <option value="{{ ContactCategoryFilter::ALL }}" @selected($categorySelectValue === ContactCategoryFilter::ALL)>All</option>
                    @foreach($categories as $categoryOption)
                        <option value="{{ $categoryOption->id }}" @selected($categorySelectValue === (string) $categoryOption->id)>{{ $categoryOption->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[12rem]">
                <label for="q" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="q" id="q" value="{{ $term }}" placeholder="Phone, name, company, email, address…" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-md" autofocus>
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Search contacts</button>
        </form>

        @if($listingAll ?? false)
            <p class="text-sm text-gray-600 mb-4">Showing <strong>{{ $selectedCategoryLabel }}</strong> (up to <strong>{{ $browseListLimit ?? \App\Support\BrowseListLimit::limit() }}</strong> records, newest first unless sorted).</p>
        @elseif($term !== '')
            <p class="text-sm text-gray-600 mb-4">Search: <strong>{{ $term }}</strong> in <strong>{{ $selectedCategoryLabel }}</strong></p>
        @endif

        @if(($listingAll ?? false) && $contacts->isEmpty())
            <p class="text-gray-600 mb-4">No contacts in this category yet. <a href="{{ route('contacts.create') }}" class="text-blue-600 hover:text-blue-800 font-medium">Create a contact</a>.</p>
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
                            <th class="px-3 py-2 text-left font-semibold">Category</th>
                            @foreach(ContactsTable::COLUMN_LABELS as $column => $label)
                                <th class="px-3 py-2 text-left font-semibold">
                                    <a href="{{ ContactsTable::sortUrl($column, $sort, $sortDir, $term, $categoryUrlParam) }}" class="text-gray-900 hover:text-blue-700 underline-offset-2 hover:underline">
                                        {{ $label }}{{ ContactsTable::sortIndicator($column, $sort, $sortDir) }}
                                    </a>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contacts as $row)
                            <tr class="hover:bg-gray-50 border-t border-gray-200">
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <a href="{{ route('contacts.show', $row) }}" class="text-blue-600 hover:text-blue-800 font-medium">Select</a>
                                </td>
                                <td class="px-3 py-2">{{ $row->contactCategory?->name ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $row->first_name ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row->surname ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row->telephone_1 ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row->telephone_2 ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row->email_address ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $row->company_name ?: '—' }}</td>
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
