@if(($contact->serviceNotes ?? collect())->isNotEmpty())
    <div
        id="related-notes-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-black/40"
        role="dialog"
        aria-modal="true"
        aria-labelledby="related-notes-modal-title"
    >
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[80vh] flex flex-col border border-gray-200">
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-200">
                <h2 id="related-notes-modal-title" class="text-lg font-bold text-gray-900">Related service note</h2>
                <button
                    type="button"
                    id="related-notes-modal-close"
                    class="text-gray-500 hover:text-gray-800 text-2xl leading-none"
                    aria-label="Close"
                >&times;</button>
            </div>
            <p class="px-5 pt-3 text-sm text-gray-600">
                Choose an existing note for this client to link the new note to.
            </p>
            <ul class="overflow-y-auto px-5 py-3 space-y-2 flex-1">
                @foreach($contact->serviceNotes as $candidate)
                    <li>
                        <a
                            href="{{ route('contacts.service-notes.create', ['contact' => $contact, 'primary_service_note_id' => $candidate->id]) }}"
                            class="block rounded-lg border border-gray-200 px-4 py-3 hover:border-indigo-400 hover:bg-indigo-50 transition-colors"
                        >
                            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600 mb-1">
                                @if($candidate->noteType)
                                    <span class="font-medium text-gray-800">{{ $candidate->noteType->name }}</span>
                                    <span class="text-gray-400">·</span>
                                @endif
                                <span class="font-mono font-semibold text-gray-800">{{ $candidate->formattedNoteNumber() }}</span>
                                <span class="text-gray-400">·</span>
                                <span @class([
                                    'inline-flex items-center px-2 py-0.5 rounded font-medium',
                                    'bg-green-100 text-green-800' => $candidate->status === \App\Models\ServiceNote::STATUS_OPEN,
                                    'bg-gray-200 text-gray-700' => $candidate->isClosed(),
                                ])>{{ ucfirst($candidate->status) }}</span>
                            </div>
                            <span class="text-sm font-semibold text-gray-900">{{ $candidate->heading }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="px-5 py-4 border-t border-gray-200">
                <button
                    type="button"
                    id="related-notes-modal-cancel"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded text-sm w-full"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>
@endif
