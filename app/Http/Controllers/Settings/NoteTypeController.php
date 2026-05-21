<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteTypeRequest;
use App\Http\Requests\UpdateNoteTypeRequest;
use App\Models\NoteType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoteTypeController extends Controller
{
    public function index(): View
    {
        $noteTypes = NoteType::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('settings.note-types.index', [
            'noteTypes' => $noteTypes,
        ]);
    }

    public function store(StoreNoteTypeRequest $request): RedirectResponse
    {
        $maxOrder = (int) NoteType::query()->max('sort_order');

        NoteType::query()->create([
            'name' => trim($request->validated('name')),
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('settings.note-types.index')
            ->with('message', 'Note type added.');
    }

    public function update(UpdateNoteTypeRequest $request, NoteType $noteType): RedirectResponse
    {
        $noteType->update([
            'name' => trim($request->validated('name')),
        ]);

        return redirect()
            ->route('settings.note-types.index')
            ->with('message', 'Note type updated.');
    }

    public function destroy(Request $request, NoteType $noteType): RedirectResponse
    {
        abort_unless($request->user()->canDeleteImeiReferenceData(), 403);

        abort_if($noteType->serviceNotes()->exists(), 403, 'Cannot delete a note type that is used on service notes.');

        $noteType->delete();

        return redirect()
            ->route('settings.note-types.index')
            ->with('message', 'Note type deleted.');
    }
}
