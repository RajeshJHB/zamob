<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImeiStatusRequest;
use App\Http\Requests\UpdateImeiStatusRequest;
use App\Models\ImeiStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImeiStatusController extends Controller
{
    public function index(): View
    {
        $statuses = ImeiStatus::query()->orderBy('status')->get();

        return view('settings.status.index', [
            'statuses' => $statuses,
        ]);
    }

    public function store(StoreImeiStatusRequest $request): RedirectResponse
    {
        ImeiStatus::query()->create([
            'status' => trim($request->validated('status')),
        ]);

        return redirect()
            ->route('settings.status.index')
            ->with('message', 'Status added.');
    }

    public function update(UpdateImeiStatusRequest $request, ImeiStatus $imeiStatus): RedirectResponse
    {
        $imeiStatus->update([
            'status' => trim($request->validated('status')),
        ]);

        return redirect()
            ->route('settings.status.index')
            ->with('message', 'Status updated.');
    }

    public function destroy(Request $request, ImeiStatus $imeiStatus): RedirectResponse
    {
        abort_unless($request->user()->canDeleteImeiReferenceData(), 403);

        $imeiStatus->delete();

        return redirect()
            ->route('settings.status.index')
            ->with('message', 'Status deleted.');
    }
}
