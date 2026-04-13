<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImeiTypeRequest;
use App\Http\Requests\UpdateImeiTypeRequest;
use App\Models\ImeiType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImeiTypeController extends Controller
{
    public function index(): View
    {
        $types = ImeiType::query()->orderBy('type')->get();

        return view('settings.types.index', [
            'types' => $types,
        ]);
    }

    public function store(StoreImeiTypeRequest $request): RedirectResponse
    {
        ImeiType::query()->create([
            'type' => trim($request->validated('type')),
        ]);

        return redirect()
            ->route('settings.types.index')
            ->with('message', 'Type added.');
    }

    public function update(UpdateImeiTypeRequest $request, ImeiType $imeiType): RedirectResponse
    {
        $imeiType->update([
            'type' => trim($request->validated('type')),
        ]);

        return redirect()
            ->route('settings.types.index')
            ->with('message', 'Type updated.');
    }

    public function destroy(Request $request, ImeiType $imeiType): RedirectResponse
    {
        abort_unless($request->user()->canDeleteImeiReferenceData(), 403);

        $imeiType->delete();

        return redirect()
            ->route('settings.types.index')
            ->with('message', 'Type deleted.');
    }
}
