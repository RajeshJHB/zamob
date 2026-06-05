@php
    /** @var \App\Models\Contact $contact */
@endphp
<dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
    <div>
        <dt class="font-medium text-gray-500">Category</dt>
        <dd class="text-gray-900">{{ $contact->contactCategory?->name ?? '—' }}</dd>
    </div>
    <div>
        <dt class="font-medium text-gray-500">Company name</dt>
        <dd class="text-gray-900">{{ $contact->company_name !== '' ? $contact->company_name : '—' }}</dd>
    </div>
    <div>
        <dt class="font-medium text-gray-500">First name</dt>
        <dd class="text-gray-900">{{ $contact->first_name !== '' ? $contact->first_name : '—' }}</dd>
    </div>
    <div>
        <dt class="font-medium text-gray-500">Surname</dt>
        <dd class="text-gray-900">{{ $contact->surname !== '' ? $contact->surname : '—' }}</dd>
    </div>
    <div>
        <dt class="font-medium text-gray-500">Telephone 1</dt>
        <dd class="text-gray-900">{{ $contact->telephone_1 !== '' ? $contact->telephone_1 : '—' }}</dd>
    </div>
    <div>
        <dt class="font-medium text-gray-500">Telephone 2</dt>
        <dd class="text-gray-900">{{ $contact->telephone_2 !== '' ? $contact->telephone_2 : '—' }}</dd>
    </div>
    <div>
        <dt class="font-medium text-gray-500">Email</dt>
        <dd class="text-gray-900">{{ $contact->email_address !== '' ? $contact->email_address : '—' }}</dd>
    </div>
    <div class="sm:col-span-2">
        <dt class="font-medium text-gray-500">Physical address</dt>
        <dd class="text-gray-900 whitespace-pre-wrap">{{ $contact->physical_address !== '' ? $contact->physical_address : '—' }}</dd>
    </div>
    <div>
        <dt class="font-medium text-gray-500">Related contact</dt>
        <dd class="text-gray-900">
            @if($contact->relatedContact)
                <a href="{{ route('contacts.show', $contact->relatedContact) }}" class="text-blue-600 hover:text-blue-800 font-medium">{{ $contact->relatedContact->displayName() }}</a>
            @else
                —
            @endif
        </dd>
    </div>
    <div>
        <dt class="font-medium text-gray-500">Created</dt>
        <dd class="text-gray-900">{{ $contact->created_at?->format('Y-m-d H:i') ?? '—' }}</dd>
    </div>
</dl>
