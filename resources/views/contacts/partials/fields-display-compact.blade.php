@php
    /** @var \App\Models\Contact $contact */
    $showCreated = $showCreated ?? false;

    $nameParts = array_values(array_filter([
        trim($contact->company_name),
        trim($contact->first_name.' '.$contact->surname),
    ], fn (string $part): bool => $part !== ''));

    $nameLine = $nameParts !== [] ? implode(' · ', $nameParts) : $contact->displayName();

    $contactLineParts = array_values(array_filter([
        $contact->telephone_1 !== '' ? $contact->telephone_1 : null,
        $contact->telephone_2 !== '' ? $contact->telephone_2 : null,
        $contact->email_address !== '' ? $contact->email_address : null,
    ]));

    $addressLine = trim($contact->physical_address);
@endphp

<div class="text-base text-gray-900 leading-snug {{ $wrapperClass ?? 'mb-4 pb-4 border-b border-gray-200' }}">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
        <div class="min-w-0 flex-1 space-y-1">
            <p class="font-semibold">{{ $nameLine }}</p>

            @if($contactLineParts !== [])
                <p>{{ implode(' · ', $contactLineParts) }}</p>
            @endif

            @if($contact->relatedContact)
                <p>
                    Related:
                    <a href="{{ route('contacts.show', $contact->relatedContact) }}" class="text-blue-600 hover:text-blue-800 font-medium">{{ $contact->relatedContact->displayName() }}</a>
                </p>
            @endif

            @if($showCreated)
                <p class="text-gray-600">Created {{ $contact->created_at?->format('Y-m-d H:i') ?? '—' }}</p>
            @endif
        </div>

        @if($addressLine !== '')
            <div class="sm:max-w-[48%] sm:text-right shrink-0">
                <p class="whitespace-pre-wrap">{{ $addressLine }}</p>
            </div>
        @endif
    </div>
</div>
