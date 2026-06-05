@php
    $isEdit = isset($contact) && $contact !== null;
    $val = function (string $key) use ($contact, $prefill): string {
        if (old($key) !== null) {
            return (string) old($key);
        }
        if ($contact !== null) {
            return (string) ($contact->{$key} ?? '');
        }

        return (string) ($prefill[$key] ?? '');
    };
    $categoryValue = function () use ($contact, $defaultCategoryId): string {
        if (old('contact_category_id') !== null) {
            return (string) old('contact_category_id');
        }
        if ($contact !== null && $contact->contact_category_id !== null) {
            return (string) $contact->contact_category_id;
        }

        return (string) ($defaultCategoryId ?? '');
    };
@endphp
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="sm:col-span-2">
        <label for="contact_category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
        <select name="contact_category_id" id="contact_category_id" required class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full max-w-md bg-white">
            @if($isEdit && $contact->contact_category_id === null)
                <option value="" disabled @selected($categoryValue() === '')>— Choose category —</option>
            @endif
            @foreach($contactCategories as $categoryOption)
                <option value="{{ $categoryOption->id }}" @selected($categoryValue() === (string) $categoryOption->id)>{{ $categoryOption->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Company name</label>
        <input type="text" name="company_name" id="company_name" value="{{ $val('company_name') }}" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full">
    </div>
    <div>
        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First name</label>
        <input type="text" name="first_name" id="first_name" value="{{ $val('first_name') }}" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full">
    </div>
    <div>
        <label for="surname" class="block text-sm font-medium text-gray-700 mb-1">Surname</label>
        <input type="text" name="surname" id="surname" value="{{ $val('surname') }}" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full">
    </div>
    <div>
        <label for="telephone_1" class="block text-sm font-medium text-gray-700 mb-1">Telephone 1</label>
        <input type="text" name="telephone_1" id="telephone_1" value="{{ $val('telephone_1') }}" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full">
    </div>
    <div>
        <label for="telephone_2" class="block text-sm font-medium text-gray-700 mb-1">Telephone 2</label>
        <input type="text" name="telephone_2" id="telephone_2" value="{{ $val('telephone_2') }}" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full">
    </div>
    <div>
        <label for="email_address" class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
        <input type="email" name="email_address" id="email_address" value="{{ $val('email_address') }}" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full">
    </div>
    <div class="sm:col-span-2">
        <label for="physical_address" class="block text-sm font-medium text-gray-700 mb-1">Physical address</label>
        <textarea name="physical_address" id="physical_address" rows="3" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full">{{ $val('physical_address') }}</textarea>
    </div>
    <div class="sm:col-span-2">
        <label for="related_contact_id" class="block text-sm font-medium text-gray-700 mb-1">Related contact (optional)</label>
        <select name="related_contact_id" id="related_contact_id" class="border border-gray-300 rounded px-3 py-2 shadow-sm w-full bg-white">
            <option value="">— None —</option>
            @foreach($contactsForRelated as $relatedOption)
                <option value="{{ $relatedOption->id }}" @selected((string) $val('related_contact_id') === (string) $relatedOption->id)>{{ $relatedOption->displayName() }}</option>
            @endforeach
        </select>
    </div>
</div>
