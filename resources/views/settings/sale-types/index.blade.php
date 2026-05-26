@extends('layouts.app')

@section('title', 'Sale types')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Sale types</h1>
        <div class="flex gap-2">
            <a href="{{ route('settings.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-1 px-3 rounded text-sm">
                IMEI Settings
            </a>
        </div>
    </div>

    @if (session('message'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    <div class="mb-8 bg-gray-50 p-4 rounded-lg">
        <h2 class="text-xl font-semibold mb-4">Add sale type</h2>
        <form method="POST" action="{{ route('settings.sale-types.store') }}" class="flex items-end gap-4">
            @csrf
            <div class="flex-1">
                <label for="new-sale-type-value" class="block text-gray-700 text-sm font-bold mb-2">Sale type</label>
                <input type="text" name="sale_type" id="new-sale-type-value" value="{{ old('sale_type') }}" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       placeholder="e.g. Cash">
            </div>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Add
            </button>
        </form>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-4">All sale types (A–Z)</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="px-4 py-2 border-b text-left">Sale type</th>
                        <th class="px-4 py-2 border-b text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($saleTypes as $row)
                        <tr id="sale-type-row-{{ $row->id }}">
                            <td class="px-4 py-2 border-b">
                                <span class="sale-type-display-{{ $row->id }}">{{ $row->sale_type }}</span>
                                <div class="sale-type-edit-{{ $row->id }}" style="display: none;">
                                    <form method="POST" action="{{ route('settings.sale-types.update', $row) }}" id="edit-sale-type-form-{{ $row->id }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="sale_type" value="{{ old('sale_type', $row->sale_type) }}" required
                                               class="shadow appearance-none border rounded py-1 px-2 text-gray-700 w-full max-w-md">
                                    </form>
                                </div>
                            </td>
                            <td class="px-4 py-2 border-b">
                                <div class="sale-type-actions-view-{{ $row->id }} flex gap-2">
                                    <button type="button" onclick="editSaleType({{ $row->id }})"
                                            class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                        Edit
                                    </button>
                                    @if(auth()->user()->canDeleteImeiReferenceData())
                                        <form method="POST" action="{{ route('settings.sale-types.destroy', $row) }}" class="inline"
                                              onsubmit="return confirm('Delete this sale type? IMEI records that use it may be affected.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <div class="sale-type-actions-edit-{{ $row->id }} flex gap-2" style="display: none;">
                                    <button type="button" onclick="saveSaleType({{ $row->id }})"
                                            class="text-green-500 hover:text-green-700 text-sm font-medium">
                                        Save
                                    </button>
                                    <button type="button" onclick="cancelEditSaleType({{ $row->id }})"
                                            class="text-gray-500 hover:text-gray-700 text-sm font-medium">
                                        Cancel
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-2 text-center text-gray-500">No sale types yet. Add one above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const originalSaleTypes = {};

    document.addEventListener('DOMContentLoaded', function () {
        @foreach($saleTypes as $row)
            originalSaleTypes[{{ $row->id }}] = @json($row->sale_type);
        @endforeach
    });

    function editSaleType(id) {
        const input = document.querySelector(`.sale-type-edit-${id} input[name="sale_type"]`);
        originalSaleTypes[id] = input.value;

        document.querySelector(`.sale-type-display-${id}`).style.display = 'none';
        document.querySelector(`.sale-type-edit-${id}`).style.display = 'block';
        document.querySelector(`.sale-type-actions-view-${id}`).style.display = 'none';
        document.querySelector(`.sale-type-actions-edit-${id}`).style.display = 'flex';
        input.focus();
    }

    function cancelEditSaleType(id) {
        const input = document.querySelector(`.sale-type-edit-${id} input[name="sale_type"]`);
        input.value = originalSaleTypes[id];

        document.querySelector(`.sale-type-display-${id}`).style.display = 'inline';
        document.querySelector(`.sale-type-edit-${id}`).style.display = 'none';
        document.querySelector(`.sale-type-actions-view-${id}`).style.display = 'flex';
        document.querySelector(`.sale-type-actions-edit-${id}`).style.display = 'none';
    }

    function saveSaleType(id) {
        document.getElementById(`edit-sale-type-form-${id}`).submit();
    }
</script>
@endsection
