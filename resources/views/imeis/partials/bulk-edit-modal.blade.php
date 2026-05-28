<div
    id="imei-bulk-edit-modal"
    class="fixed inset-0 z-50 hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="imei-bulk-edit-title"
>
    <div class="absolute inset-0 bg-black/40" id="imei-bulk-edit-backdrop"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl border border-gray-200 max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-gray-200 sticky top-0 bg-white z-10">
                <h2 id="imei-bulk-edit-title" class="text-xl font-bold text-gray-900">Bulk Edit</h2>
                <button type="button" id="imei-bulk-edit-close" class="text-gray-500 hover:text-gray-800 text-2xl leading-none" aria-label="Close">&times;</button>
            </div>
            <form method="POST" action="{{ route('imeis.bulk-edit') }}" class="px-6 py-4 space-y-5">
                @csrf
                @foreach($filterParams ?? [] as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $item)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <p class="text-sm text-gray-700">
                    Within the current filtered results (<strong>{{ number_format($bulkEditCount) }}</strong> record(s)),
                    find records matching the search values below and apply the replace values.
                    Deal Details and Customer Details search uses <strong>contains</strong> matching.
                </p>

                @if ($errors->has('search_sale_type') || $errors->has('replace_sale_type'))
                    <div class="bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->get('search_sale_type') as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                            @foreach ($errors->get('replace_sale_type') as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <fieldset class="space-y-4 border border-gray-200 rounded-lg p-4">
                        <legend class="text-sm font-semibold text-gray-900 px-1">Search</legend>

                        <div>
                            <label for="search_sale_type" class="block text-sm font-medium text-gray-700 mb-1">Sale Type</label>
                            <select name="search_sale_type" id="search_sale_type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="">— Any —</option>
                                @foreach($saleTypeOptions as $saleTypeOption)
                                    <option value="{{ $saleTypeOption }}" @selected(old('search_sale_type') === $saleTypeOption)>{{ $saleTypeOption }}</option>
                                @endforeach
                            </select>
                            @error('search_sale_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="search_status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="search_status" id="search_status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="">— Any —</option>
                                @foreach($statusOptions as $statusOption)
                                    <option value="{{ $statusOption }}" @selected(old('search_status') === $statusOption)>{{ $statusOption }}</option>
                                @endforeach
                            </select>
                            @error('search_status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="search_deal_details" class="block text-sm font-medium text-gray-700 mb-1">Deal Details</label>
                            <input
                                type="text"
                                name="search_deal_details"
                                id="search_deal_details"
                                value="{{ old('search_deal_details') }}"
                                placeholder="Contains…"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                                autocomplete="off"
                            >
                            @error('search_deal_details')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="search_customer_details" class="block text-sm font-medium text-gray-700 mb-1">Customer Details</label>
                            <input
                                type="text"
                                name="search_customer_details"
                                id="search_customer_details"
                                value="{{ old('search_customer_details') }}"
                                placeholder="Contains…"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                                autocomplete="off"
                            >
                            @error('search_customer_details')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </fieldset>

                    <fieldset class="space-y-4 border border-gray-200 rounded-lg p-4">
                        <legend class="text-sm font-semibold text-gray-900 px-1">Replace</legend>

                        <div>
                            <label for="replace_sale_type" class="block text-sm font-medium text-gray-700 mb-1">Sale Type</label>
                            <select name="replace_sale_type" id="replace_sale_type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="">— No change —</option>
                                @foreach($saleTypeOptions as $saleTypeOption)
                                    <option value="{{ $saleTypeOption }}" @selected(old('replace_sale_type') === $saleTypeOption)>{{ $saleTypeOption }}</option>
                                @endforeach
                            </select>
                            @error('replace_sale_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="replace_status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="replace_status" id="replace_status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="">— No change —</option>
                                @foreach($statusOptions as $statusOption)
                                    <option value="{{ $statusOption }}" @selected(old('replace_status') === $statusOption)>{{ $statusOption }}</option>
                                @endforeach
                            </select>
                            @error('replace_status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="replace_deal_details" class="block text-sm font-medium text-gray-700 mb-1">Deal Details</label>
                            <input
                                type="text"
                                name="replace_deal_details"
                                id="replace_deal_details"
                                value="{{ old('replace_deal_details') }}"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                                autocomplete="off"
                            >
                            @error('replace_deal_details')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="replace_customer_details" class="block text-sm font-medium text-gray-700 mb-1">Customer Details</label>
                            <input
                                type="text"
                                name="replace_customer_details"
                                id="replace_customer_details"
                                value="{{ old('replace_customer_details') }}"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                                autocomplete="off"
                            >
                            @error('replace_customer_details')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </fieldset>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-200">
                    <button type="button" id="imei-bulk-edit-cancel" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded text-sm">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        id="imei-bulk-edit-submit"
                        class="bg-teal-600 hover:bg-teal-800 text-white font-bold py-2 px-4 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        @disabled($bulkEditCount === 0)
                    >
                        Execute
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const bulkEditOpenButton = document.getElementById('imei-bulk-edit-open');
    const bulkEditModal = document.getElementById('imei-bulk-edit-modal');
    const bulkEditBackdrop = document.getElementById('imei-bulk-edit-backdrop');
    const bulkEditCloseButton = document.getElementById('imei-bulk-edit-close');
    const bulkEditCancelButton = document.getElementById('imei-bulk-edit-cancel');

    function openBulkEditModal() {
        if (! bulkEditModal) {
            return;
        }
        bulkEditModal.classList.remove('hidden');
    }

    function closeBulkEditModal() {
        if (! bulkEditModal) {
            return;
        }
        bulkEditModal.classList.add('hidden');
    }

    if (bulkEditOpenButton) {
        bulkEditOpenButton.addEventListener('click', openBulkEditModal);
    }

    if (bulkEditBackdrop) {
        bulkEditBackdrop.addEventListener('click', closeBulkEditModal);
    }

    if (bulkEditCloseButton) {
        bulkEditCloseButton.addEventListener('click', closeBulkEditModal);
    }

    if (bulkEditCancelButton) {
        bulkEditCancelButton.addEventListener('click', closeBulkEditModal);
    }

    @if(
        $errors->has('search_sale_type')
        || $errors->has('search_status')
        || $errors->has('search_deal_details')
        || $errors->has('search_customer_details')
        || $errors->has('replace_sale_type')
        || $errors->has('replace_status')
        || $errors->has('replace_deal_details')
        || $errors->has('replace_customer_details')
    )
        openBulkEditModal();
    @endif
});
</script>
