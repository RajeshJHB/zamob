@extends('layouts.app')

@section('title', 'Contact categories')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Contact categories</h1>
        <div class="flex gap-2">
            <a href="{{ route('settings.contacts.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-1 px-3 rounded text-sm">
                Contact Settings
            </a>
        </div>
    </div>

    @if (session('message'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-8 bg-gray-50 p-4 rounded-lg">
        <h2 class="text-xl font-semibold mb-4">Add contact category</h2>
        <form method="POST" action="{{ route('settings.contact-categories.store') }}" class="flex items-end gap-4">
            @csrf
            <div class="flex-1">
                <label for="new-contact-category-name" class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                <input type="text" name="name" id="new-contact-category-name" value="{{ old('name') }}" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       placeholder="e.g. Courier">
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add
            </button>
        </form>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-4">All contact categories</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 border-b text-left">Name</th>
                        <th class="px-4 py-2 border-b text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $row)
                        <tr id="contact-category-row-{{ $row->id }}">
                            <td class="px-4 py-2 border-b">
                                <span class="contact-category-display-{{ $row->id }}">{{ $row->name }}</span>
                                <div class="contact-category-edit-{{ $row->id }}" style="display: none;">
                                    <form method="POST" action="{{ route('settings.contact-categories.update', $row) }}" id="edit-contact-category-form-{{ $row->id }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" value="{{ old('name', $row->name) }}" required
                                               class="shadow appearance-none border rounded py-1 px-2 text-gray-700 w-full max-w-md">
                                    </form>
                                </div>
                            </td>
                            <td class="px-4 py-2 border-b">
                                <div class="contact-category-actions-view-{{ $row->id }} flex gap-2">
                                    <button type="button" onclick="editContactCategory({{ $row->id }})"
                                            class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                        Edit
                                    </button>
                                    @if(auth()->user()->canDeleteImeiReferenceData())
                                        <form method="POST" action="{{ route('settings.contact-categories.destroy', $row) }}" class="inline"
                                              onsubmit="return confirm('Delete this contact category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <div class="contact-category-actions-edit-{{ $row->id }} flex gap-2" style="display: none;">
                                    <button type="button" onclick="saveContactCategory({{ $row->id }})"
                                            class="text-green-500 hover:text-green-700 text-sm font-medium">
                                        Save
                                    </button>
                                    <button type="button" onclick="cancelEditContactCategory({{ $row->id }})"
                                            class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                                        Cancel
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-2 text-center text-gray-500">No contact categories yet. Add one above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const originalContactCategoryNames = {};

    document.addEventListener('DOMContentLoaded', function () {
        @foreach($categories as $row)
            originalContactCategoryNames[{{ $row->id }}] = @json($row->name);
        @endforeach
    });

    function editContactCategory(id) {
        const input = document.querySelector(`.contact-category-edit-${id} input[name="name"]`);
        originalContactCategoryNames[id] = input.value;

        document.querySelector(`.contact-category-display-${id}`).style.display = 'none';
        document.querySelector(`.contact-category-edit-${id}`).style.display = 'block';
        document.querySelector(`.contact-category-actions-view-${id}`).style.display = 'none';
        document.querySelector(`.contact-category-actions-edit-${id}`).style.display = 'flex';
        input.focus();
    }

    function cancelEditContactCategory(id) {
        const input = document.querySelector(`.contact-category-edit-${id} input[name="name"]`);
        input.value = originalContactCategoryNames[id];

        document.querySelector(`.contact-category-display-${id}`).style.display = 'inline';
        document.querySelector(`.contact-category-edit-${id}`).style.display = 'none';
        document.querySelector(`.contact-category-actions-view-${id}`).style.display = 'flex';
        document.querySelector(`.contact-category-actions-edit-${id}`).style.display = 'none';
    }

    function saveContactCategory(id) {
        document.getElementById(`edit-contact-category-form-${id}`).submit();
    }
</script>
@endsection
