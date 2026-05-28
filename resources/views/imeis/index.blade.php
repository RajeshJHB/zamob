@extends('layouts.app')

@section('contentWidth', 'full')

@section('title', 'IMEI')

@section('content')
@php
    use Illuminate\Support\Str;

    $listReturnQuery = http_build_query(array_filter(array_merge(
        $filterParams ?? [],
        request()->only(['page']),
    ), fn (mixed $value): bool => $value !== null && $value !== ''));
    $imeiBrowseTextDisplayLimit = 150;
@endphp
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="grid grid-cols-1 sm:grid-cols-3 items-center gap-3 mb-6">
        <div class="sm:justify-self-start">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-bold">IMEI's</h1>
                <div class="flex items-center gap-2">
                    <label for="imei-profile-select" class="text-xs font-semibold text-gray-600">Profile</label>
                    <select
                        id="imei-profile-select"
                        class="border border-gray-300 rounded-md py-1.5 px-2 text-sm text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    >
                        <option value="" @selected(empty($activeProfileId))>(none)</option>
                        @foreach(($savedFilters ?? collect()) as $profile)
                            <option value="{{ $profile->id }}" @selected((int) $activeProfileId === (int) $profile->id)>{{ $profile->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if(! empty($currentProfileName))
                <div class="mt-1 text-sm font-semibold text-gray-700">
                    Searched Profile: "{{ $currentProfileName }}"
                </div>
            @endif
        </div>
        <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-self-center">
            <button type="button" id="imei-add-open-btn" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2.5 px-5 rounded-md text-base shadow-sm ring-2 ring-blue-200 inline-block text-center shrink-0">
                Add IMEI
            </button>
            <form method="GET" action="{{ route('imeis.index') }}" class="flex items-center gap-2 min-w-0">
                <input type="hidden" name="quick_search" value="1">
                @if(! empty($activeProfileId))
                    <input type="hidden" name="profile_id" value="{{ $activeProfileId }}">
                @endif
                @foreach($filterParams ?? [] as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $item)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                        @endforeach
                    @elseif(! in_array($key, ['search', 'page', 'profile_id'], true))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Quick search…"
                    class="border border-gray-300 rounded-md py-2 px-3 text-sm text-gray-900 shadow-sm w-40 sm:w-52 min-w-0 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <button type="submit" class="bg-gray-700 hover:bg-gray-900 text-white font-bold py-2 px-4 rounded-md text-sm shadow-sm shrink-0">
                    Quick search
                </button>
                <a
                    href="{{ route('imeis.search.reset') }}"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-md text-sm shadow-sm shrink-0 inline-block text-center"
                >
                    Reset search
                </a>
            </form>
        </div>
        <div class="flex flex-wrap items-center justify-start sm:justify-end gap-2 sm:justify-self-end">
            <a href="{{ route('imeis.filter', $filterParams ?? []) }}" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2.5 px-5 rounded-md text-base shadow-sm ring-2 ring-blue-200 inline-block">
                Filter
            </a>
            @if($canBulkEditImei ?? false)
                <button
                    type="button"
                    id="imei-bulk-edit-open"
                    class="bg-teal-600 hover:bg-teal-800 text-white font-bold py-1 px-3 rounded text-sm"
                >
                    Bulk Edit
                </button>
            @endif
            @if($canBulkChangeStatus ?? false)
                <div class="relative" id="imei-advanced-menu-container">
                    <button id="imei-advanced-menu-button" type="button" class="bg-indigo-600 hover:bg-indigo-800 text-white font-bold py-1 px-3 rounded text-sm flex items-center gap-1">
                        Advanced
                        <svg id="imei-advanced-menu-arrow" class="h-3 w-3 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div id="imei-advanced-menu-dropdown"
                         class="absolute right-0 mt-1 w-52 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200 hidden">
                        <button type="button" id="imei-bulk-status-open" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            Bulk change Status
                        </button>
                    </div>
                </div>
            @endif
            <a href="{{ route('imeis.print', $filterParams ?? []) }}" target="_blank" class="bg-gray-600 hover:bg-gray-800 text-white font-bold py-1 px-3 rounded text-sm">
                Print all results
            </a>
        </div>
    </div>

    @if (session('message'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">
            {{ session('message') }}
        </div>
    @endif

    <div class="-mx-6 overflow-x-auto overscroll-x-contain">
        <table class="w-max min-w-full border-collapse bg-white border border-gray-300">
            <thead>
                <tr class="bg-gray-100">
                    <th class="whitespace-nowrap border border-gray-300 px-1.5 py-1.5 text-left text-xs font-semibold text-gray-700">Print</th>
                    <th class="whitespace-nowrap border border-gray-300 px-1.5 py-1.5 text-left text-xs font-semibold text-gray-700">Edit</th>
                    @foreach($columns as $col)
                        <th class="whitespace-nowrap border border-gray-300 px-3 py-2 text-left text-xs font-semibold text-gray-700">{{ $columnLabels[$col] ?? $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($imeis as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap border border-gray-300 px-1.5 py-1.5 text-xs">
                            <a href="{{ route('imeis.receipt', $row) }}" target="_blank" rel="noopener noreferrer" class="text-blue-700 hover:text-blue-900 font-medium underline">Print</a>
                        </td>
                        <td class="whitespace-nowrap border border-gray-300 px-1.5 py-1.5 text-xs">
                            <a href="{{ route('imeis.edit', $row).($listReturnQuery !== '' ? '?return_query='.rawurlencode($listReturnQuery) : '') }}" class="text-blue-700 hover:text-blue-900 font-medium underline">Edit</a>
                        </td>
                        @foreach($columns as $col)
                            <td @class([
                                'border border-gray-300 px-3 py-2 text-sm text-gray-700',
                                'whitespace-nowrap' => ! in_array($col, ['notes', 'ref'], true),
                            ])>
                                @if($col === 'date_in' || $col === 'date_updated')
                                    {{ $row->$col?->format('Y-m-d H:i') ?? '—' }}
                                @elseif($col === 'cost_incl')
                                    {{ \App\Support\ImeiCostIncl::format($row->cost_excl) ?? '—' }}
                                @elseif($col === 'notes' || $col === 'ref')
                                    @php $cellText = (string) ($row->$col ?? ''); @endphp
                                    @if($cellText !== '')
                                        <span class="inline-block max-w-xs" title="{{ $cellText }}">{{ Str::limit($cellText, $imeiBrowseTextDisplayLimit) }}</span>
                                    @else
                                        —
                                    @endif
                                @else
                                    {{ $row->$col ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 2 + count($columns) }}" class="border border-gray-300 px-3 py-8 text-center text-gray-500">No IMEI records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($imeis->hasPages())
        <div class="mt-4">
            {{ $imeis->links() }}
        </div>
    @endif
</div>

<div
    id="imei-add-modal"
    class="fixed inset-0 z-50 hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="imei-add-modal-title"
>
    <div class="absolute inset-0 bg-black/40" id="imei-add-backdrop"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[92vh] flex flex-col border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-gray-200 shrink-0">
                <h2 id="imei-add-modal-title" class="text-lg font-bold text-gray-900">Add IMEI</h2>
                <button type="button" id="imei-add-modal-close" class="text-gray-500 hover:text-gray-800 text-2xl leading-none" aria-label="Close">&times;</button>
            </div>
            <iframe
                id="imei-add-modal-frame"
                name="imei-add-modal-frame"
                class="w-full flex-1 min-h-[75vh] border-0 bg-white"
                title="Add IMEI"
            ></iframe>
        </div>
    </div>
</div>

@if($canBulkChangeStatus ?? false)
<div
    id="imei-bulk-status-modal"
    class="fixed inset-0 z-50 hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="imei-bulk-status-title"
>
    <div class="absolute inset-0 bg-black/40" id="imei-bulk-status-backdrop"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg border border-gray-200">
            <div class="flex items-center justify-between gap-3 px-6 py-4 border-b border-gray-200">
                <h2 id="imei-bulk-status-title" class="text-xl font-bold text-gray-900">Bulk change Status</h2>
                <button type="button" id="imei-bulk-status-close" class="text-gray-500 hover:text-gray-800 text-2xl leading-none" aria-label="Close">&times;</button>
            </div>
            <form method="POST" action="{{ route('imeis.bulk-status') }}" class="px-6 py-4 space-y-4">
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
                    Change status for <strong>{{ number_format($bulkStatusCount) }}</strong> record(s) in this search
                    from <strong>{{ $statusFilterValue }}</strong> to:
                </p>

                <div>
                    <label for="status_to" class="block text-sm font-medium text-gray-700 mb-1">New status</label>
                    <select name="status_to" id="status_to" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        <option value="">Choose status…</option>
                        @foreach($statusOptions as $statusOption)
                            @if($statusOption !== $statusFilterValue)
                                <option value="{{ $statusOption }}" @selected(old('status_to') === $statusOption)>{{ $statusOption }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" id="imei-bulk-status-cancel" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded text-sm">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        id="imei-bulk-status-submit"
                        class="bg-indigo-600 hover:bg-indigo-800 text-white font-bold py-2 px-4 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        @disabled($bulkStatusCount === 0)
                    >
                        Update {{ number_format($bulkStatusCount) }} record(s)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const advancedButton = document.getElementById('imei-advanced-menu-button');
    const advancedDropdown = document.getElementById('imei-advanced-menu-dropdown');
    const advancedArrow = document.getElementById('imei-advanced-menu-arrow');
    const advancedContainer = document.getElementById('imei-advanced-menu-container');
    const bulkOpenButton = document.getElementById('imei-bulk-status-open');
    const modal = document.getElementById('imei-bulk-status-modal');
    const backdrop = document.getElementById('imei-bulk-status-backdrop');
    const closeButton = document.getElementById('imei-bulk-status-close');
    const cancelButton = document.getElementById('imei-bulk-status-cancel');

    function closeAdvancedMenu() {
        if (! advancedDropdown) {
            return;
        }
        advancedDropdown.classList.add('hidden');
        if (advancedArrow) {
            advancedArrow.style.transform = 'rotate(0deg)';
        }
    }

    function openModal() {
        if (! modal) {
            return;
        }
        modal.classList.remove('hidden');
        closeAdvancedMenu();
    }

    function closeModal() {
        if (! modal) {
            return;
        }
        modal.classList.add('hidden');
    }

    if (advancedButton && advancedDropdown) {
        advancedButton.addEventListener('click', function (event) {
            event.stopPropagation();
            const isHidden = advancedDropdown.classList.contains('hidden');
            if (isHidden) {
                advancedDropdown.classList.remove('hidden');
                if (advancedArrow) {
                    advancedArrow.style.transform = 'rotate(180deg)';
                }
            } else {
                closeAdvancedMenu();
            }
        });

        document.addEventListener('click', function (event) {
            if (advancedContainer && ! advancedContainer.contains(event.target)) {
                closeAdvancedMenu();
            }
        });
    }

    if (bulkOpenButton) {
        bulkOpenButton.addEventListener('click', openModal);
    }

    if (backdrop) {
        backdrop.addEventListener('click', closeModal);
    }

    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', closeModal);
    }

    @if($errors->has('status_to') || $errors->has('field_filter'))
        openModal();
    @endif
});
</script>
@endif

@if($canBulkEditImei ?? false)
    @include('imeis.partials.bulk-edit-modal')
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const addModal = document.getElementById('imei-add-modal');
    const addFrame = document.getElementById('imei-add-modal-frame');
    const addOpenButton = document.getElementById('imei-add-open-btn');
    const addCloseButton = document.getElementById('imei-add-modal-close');
    const addBackdrop = document.getElementById('imei-add-backdrop');
    const createEmbeddedUrl = @json(route('imeis.create', ['embedded' => 1]));

    function closeAddModal(reload) {
        if (! addModal) {
            return;
        }

        addModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');

        if (addFrame) {
            addFrame.src = 'about:blank';
        }

        if (reload) {
            window.location.reload();
        }
    }

    function openAddModal() {
        if (! addModal || ! addFrame) {
            return;
        }

        addFrame.src = createEmbeddedUrl;
        addModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    if (addOpenButton) {
        addOpenButton.addEventListener('click', openAddModal);
    }

    if (addCloseButton) {
        addCloseButton.addEventListener('click', function () {
            closeAddModal(false);
        });
    }

    if (addBackdrop) {
        addBackdrop.addEventListener('click', function () {
            closeAddModal(false);
        });
    }

    window.addEventListener('message', function (event) {
        if (event.origin !== window.location.origin) {
            return;
        }

        if (event.data && event.data.type === 'imei-form-close') {
            closeAddModal(!! event.data.reload);
        }
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const profileSelect = document.getElementById('imei-profile-select');

    if (! profileSelect) {
        return;
    }

    profileSelect.addEventListener('change', function () {
        const id = profileSelect.value;

        if (! id) {
            window.location.href = '{{ route('imeis.profile.clear') }}';
            return;
        }

        window.location.href = '{{ url('/imeis/profile/apply') }}' + '/' + encodeURIComponent(id);
    });
});
</script>
@endsection
