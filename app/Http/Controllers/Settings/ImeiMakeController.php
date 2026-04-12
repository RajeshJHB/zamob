<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImeiMakeRequest;
use App\Http\Requests\UpdateImeiMakeRequest;
use App\Models\ImeiMake;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ImeiMakeController extends Controller
{
    public function index(): View
    {
        $makes = ImeiMake::query()->orderBy('make')->get();

        return view('settings.makes.index', [
            'makes' => $makes,
        ]);
    }

    public function store(StoreImeiMakeRequest $request): RedirectResponse
    {
        ImeiMake::query()->create([
            'make' => trim($request->validated('make')),
        ]);

        return redirect()
            ->route('settings.makes.index')
            ->with('message', 'Make added.');
    }

    public function update(UpdateImeiMakeRequest $request, ImeiMake $imeiMake): RedirectResponse
    {
        $imeiMake->update([
            'make' => trim($request->validated('make')),
        ]);

        return redirect()
            ->route('settings.makes.index')
            ->with('message', 'Make updated.');
    }

    public function destroy(ImeiMake $imeiMake): RedirectResponse
    {
        $imeiMake->delete();

        return redirect()
            ->route('settings.makes.index')
            ->with('message', 'Make deleted.');
    }
}
