<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImeiLocationRequest;
use App\Http\Requests\UpdateImeiLocationRequest;
use App\Models\ImeiLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImeiLocationController extends Controller
{
    public function index(): View
    {
        $locations = ImeiLocation::query()->orderBy('location')->get();

        return view('settings.locations.index', [
            'locations' => $locations,
        ]);
    }

    public function store(StoreImeiLocationRequest $request): RedirectResponse
    {
        ImeiLocation::query()->create([
            'location' => trim($request->validated('location')),
        ]);

        return redirect()
            ->route('settings.locations.index')
            ->with('message', 'Location added.');
    }

    public function update(UpdateImeiLocationRequest $request, ImeiLocation $imeiLocation): RedirectResponse
    {
        $imeiLocation->update([
            'location' => trim($request->validated('location')),
        ]);

        return redirect()
            ->route('settings.locations.index')
            ->with('message', 'Location updated.');
    }

    public function destroy(Request $request, ImeiLocation $imeiLocation): RedirectResponse
    {
        abort_unless($request->user()->canDeleteImeiReferenceData(), 403);

        $imeiLocation->delete();

        return redirect()
            ->route('settings.locations.index')
            ->with('message', 'Location deleted.');
    }
}
