<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchNotesRequest;
use App\Models\NoteType;
use App\Models\ServiceNote;
use App\Support\ServiceNoteBrowsePageSize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotesController extends Controller
{
    public function search(SearchNotesRequest $request): View
    {
        $term = trim((string) $request->input('note_q', ''));
        $noteTypeScope = $request->noteTypeScope();
        $noteTypeIds = $request->resolvedNoteTypeIds();
        $noteStatusFilter = $this->resolvedNoteStatus($request);
        $noteStatusInput = $request->has('note_status')
            ? (string) $request->input('note_status', '')
            : '';
        $onlyMine = $request->onlyMine();
        $startDate = $request->startDate();
        $endDate = $request->endDate();
        $hasDateFilter = $request->hasDateFilter();
        $listingAll = $term === '' && $noteTypeIds === [] && $noteStatusFilter === null && ! $onlyMine && ! $hasDateFilter;
        $listingMyNotesDefault = $term === '' && $noteTypeIds === [] && $noteStatusFilter === null && $onlyMine && ! $hasDateFilter;
        $listingOpenDefault = $term === '' && $noteTypeIds === [] && $noteStatusFilter === ServiceNote::STATUS_OPEN && ! $onlyMine && ! $hasDateFilter;
        $noteTypes = $this->noteTypesForSelect();
        $selectedNoteTypes = $noteTypes->whereIn('id', $noteTypeIds)->values();

        $notes = $this->buildNotesSearchQuery($request)
            ->paginate(ServiceNoteBrowsePageSize::SIZE)
            ->withQueryString();

        return view('notes.search', [
            'term' => $term,
            'noteTypeScope' => $noteTypeScope,
            'noteTypeIds' => $noteTypeIds,
            'noteStatus' => $noteStatusInput,
            'noteStatusFilter' => $noteStatusFilter,
            'onlyMine' => $onlyMine,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'noteTypes' => $noteTypes,
            'selectedNoteTypes' => $selectedNoteTypes,
            'notes' => $notes,
            'listingAll' => $listingAll,
            'listingMyNotesDefault' => $listingMyNotesDefault,
            'listingOpenDefault' => $listingOpenDefault,
        ]);
    }

    public function print(SearchNotesRequest $request): View
    {
        $notes = $this->buildNotesSearchQuery($request)->get();

        return view('notes.print', [
            'notes' => $notes,
        ]);
    }

    public function legacyContactsNotesSearchRedirect(Request $request): RedirectResponse
    {
        return redirect()->route('notes.index', $request->query());
    }

    public function legacyNotesSearchRedirect(Request $request): RedirectResponse
    {
        return redirect()->route('notes.index', $request->query());
    }

    /**
     * @return Builder<ServiceNote>
     */
    private function buildNotesSearchQuery(SearchNotesRequest $request): Builder
    {
        $term = trim((string) $request->input('note_q', ''));
        $noteTypeIds = $request->resolvedNoteTypeIds();
        $noteStatus = $this->resolvedNoteStatus($request);
        $startDate = $request->startDate();
        $endDate = $request->endDate();

        $query = ServiceNote::query()
            ->with(['contact', 'noteType', 'author'])
            ->orderByDesc('noted_at')
            ->orderByDesc('id');

        if ($noteTypeIds !== []) {
            $query->whereIn('note_type_id', $noteTypeIds);
        }

        if ($noteStatus !== null) {
            $query->where('status', $noteStatus);
        }

        if ($request->onlyMine()) {
            $userId = $request->user()?->id;
            if ($userId !== null) {
                $query->where('created_by', $userId);
            }
        }

        if ($startDate !== null) {
            $query->where('noted_at', '>=', $startDate.' 00:00:00');
        }

        if ($endDate !== null) {
            $query->where('noted_at', '<=', $endDate.' 23:59:59');
        }

        if ($term !== '') {
            $query->searchTerm($term);
        }

        return $query;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, NoteType>
     */
    private function noteTypesForSelect(): \Illuminate\Database\Eloquent\Collection
    {
        return NoteType::query()->orderBy('name')->get();
    }

    private function resolvedNoteStatus(SearchNotesRequest $request): ?string
    {
        if (! $request->has('note_status')) {
            return null;
        }

        $raw = (string) $request->input('note_status', '');

        if ($raw === '') {
            return null;
        }

        return in_array($raw, [ServiceNote::STATUS_OPEN, ServiceNote::STATUS_CLOSED], true)
            ? $raw
            : null;
    }
}
