@extends('layouts.app')

@section('title', 'Help')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 space-y-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Help</h1>
            <p class="mt-2 text-gray-700">
                This page explains what each menu option does and how to fill in the main screens.
            </p>
        </div>

        <div class="shrink-0">
            <button
                type="button"
                id="help-version-btn"
                class="bg-gray-900 hover:bg-gray-800 text-white font-bold py-2 px-4 rounded text-sm"
            >
                Version
            </button>
            <span id="help-version-text" class="ml-3 text-sm font-semibold text-gray-900 hidden">
                {{ $version }}
            </span>

            @if($showUpdateHistory)
                <button
                    type="button"
                    id="help-update-history-btn"
                    class="ml-4 bg-indigo-600 hover:bg-indigo-800 text-white font-bold py-2 px-4 rounded text-sm"
                >
                    Update History
                </button>
            @endif
        </div>
    </div>

    @if($showUpdateHistory)
        <div
            id="help-update-history-container"
            class="hidden border border-gray-200 rounded-lg p-4"
        >
            <h2 class="text-xl font-bold text-gray-900">Update History (Role 4)</h2>
            <div class="mt-3 space-y-4">
                @foreach($updateHistory as $date => $items)
                    <div>
                        <div class="font-semibold text-gray-900">{{ $date }}</div>
                        <ul class="mt-2 list-disc pl-5 text-gray-700 space-y-1">
                            @foreach($items as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="border border-gray-200 rounded-lg p-4">
        <h2 class="text-xl font-bold text-gray-900">Menu options</h2>

        <div class="mt-4 space-y-4 text-gray-700">
            <div>
                <div class="font-semibold text-gray-900">Home</div>
                <div>Shows the Cash Devices list.</div>
            </div>
            <div>
                <div class="font-semibold text-gray-900">IMEI</div>
                <div>Browse, filter, add, and edit IMEI/device records.</div>
            </div>
            <div>
                <div class="font-semibold text-gray-900">Contacts</div>
                <div>Search contacts, manage contact details, and service notes.</div>
            </div>
            <div>
                <div class="font-semibold text-gray-900">Notes</div>
                <div>Search service notes by type and date range and print results.</div>
            </div>
            <div>
                <div class="font-semibold text-gray-900">Settings</div>
                <div>Manage IMEI reference data (makes/models/locations/types/status/sale types), note settings, VAT, and defaults.</div>
            </div>
            <div>
                <div class="font-semibold text-gray-900">Profile</div>
                <div>View and edit your profile information.</div>
            </div>
            <div>
                <div class="font-semibold text-gray-900">Password Reset</div>
                <div>Change your login password.</div>
            </div>
            <div>
                <div class="font-semibold text-gray-900">Logout</div>
                <div>Signs you out of the system.</div>
            </div>
        </div>
    </div>

    <div class="border border-gray-200 rounded-lg p-4">
        <h2 class="text-xl font-bold text-gray-900">IMEI screen (fields)</h2>
        <div class="mt-3 text-gray-700 space-y-3">
            <div><span class="font-semibold text-gray-900">Date in</span>: When the device was received into stock.</div>
            <div><span class="font-semibold text-gray-900">Date updated</span>: Last updated date/time on the legacy record.</div>
            <div><span class="font-semibold text-gray-900">Make / Model</span>: Device manufacturer and model.</div>
            <div><span class="font-semibold text-gray-900">IMEI</span>: Device IMEI number (used for lookup and uniqueness).</div>
            <div><span class="font-semibold text-gray-900">Serial Number</span>: Optional serial number.</div>
            <div><span class="font-semibold text-gray-900">Location</span>: Store or location for the device.</div>
            <div><span class="font-semibold text-gray-900">Type</span>: Device type/category.</div>
            <div><span class="font-semibold text-gray-900">Status</span>: Device status (e.g. available / sold / deleted depending on your setup).</div>
            <div><span class="font-semibold text-gray-900">Sale type</span>: How the device was sold (None, Cash, Voda_Sale, Easy20wn, or other values from IMEI Settings).</div>
            <div><span class="font-semibold text-gray-900">Customer Details</span>: Notes about the customer/deal.</div>
            <div><span class="font-semibold text-gray-900">Deal Phone Number</span>: Phone number related to the deal.</div>
            <div><span class="font-semibold text-gray-900">Deal Details</span>: Deal reference/details.</div>
            <div><span class="font-semibold text-gray-900">Staff</span>: Staff member responsible.</div>
            <div><span class="font-semibold text-gray-900">Item code</span>: Optional internal item code.</div>
            <div><span class="font-semibold text-gray-900">Cost excl</span>: Cost price excluding VAT.</div>
            <div><span class="font-semibold text-gray-900">Cost incl</span>: Calculated cost including VAT (from VAT setting).</div>
            <div><span class="font-semibold text-gray-900">Selling price</span>: Selling price amount.</div>
        </div>
    </div>

    <div class="border border-gray-200 rounded-lg p-4">
        <h2 class="text-xl font-bold text-gray-900">Contacts & Service Notes (fields)</h2>
        <div class="mt-3 text-gray-700 space-y-3">
            <div><span class="font-semibold text-gray-900">Contact</span>: Stores customer contact details.</div>
            <div><span class="font-semibold text-gray-900">Service Note</span>: A note linked to a contact, with a type, content, and timestamps.</div>
            <div><span class="font-semibold text-gray-900">Start Time / Last Time</span>: The created and last updated time shown on notes and contact lists.</div>
            <div><span class="font-semibold text-gray-900">Attachments</span>: Files attached to service notes (downloadable/printable where available).</div>
        </div>
    </div>

    <div class="border border-gray-200 rounded-lg p-4">
        <h2 class="text-xl font-bold text-gray-900">Notes search</h2>
        <div class="mt-3 text-gray-700 space-y-3">
            <div><span class="font-semibold text-gray-900">All note types</span>: Searches across every note type.</div>
            <div><span class="font-semibold text-gray-900">Selected types only</span>: Choose one or more note types to filter results.</div>
            <div><span class="font-semibold text-gray-900">Start date / End date</span>: Restrict results to a date range (end date must be on/after start date).</div>
            <div><span class="font-semibold text-gray-900">Print</span>: Prints all results for the current filters.</div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('help-version-btn');
    const text = document.getElementById('help-version-text');

    if (! button || ! text) {
        return;
    }

    button.addEventListener('click', function () {
        text.classList.toggle('hidden');
    });

    const updateButton = document.getElementById('help-update-history-btn');
    const updateContainer = document.getElementById('help-update-history-container');

    if (! updateButton || ! updateContainer) {
        return;
    }

    updateButton.addEventListener('click', function () {
        updateContainer.classList.toggle('hidden');
    });
});
</script>
@endsection

