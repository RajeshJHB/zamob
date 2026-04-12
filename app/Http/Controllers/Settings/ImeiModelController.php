<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImeiModelRequest;
use App\Http\Requests\UpdateImeiModelRequest;
use App\Models\ImeiMake;
use App\Models\ImeiModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImeiModelController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $makes = ImeiMake::query()->orderBy('make')->get();
        $selectedMake = $request->query('make');
        if (($selectedMake === null || $selectedMake === '') && is_string($request->old('make')) && $request->old('make') !== '') {
            $selectedMake = $request->old('make');
        }

        if ($selectedMake === null || $selectedMake === '') {
            return view('settings.models.index', [
                'makes' => $makes,
                'selectedMake' => null,
                'models' => collect(),
            ]);
        }

        if (! ImeiMake::query()->where('make', $selectedMake)->exists()) {
            return redirect()
                ->route('settings.models.index')
                ->with('error', 'Please choose a valid make from the list.');
        }

        $models = ImeiModel::query()
            ->where('make', $selectedMake)
            ->orderBy('model')
            ->get();

        return view('settings.models.index', [
            'makes' => $makes,
            'selectedMake' => $selectedMake,
            'models' => $models,
        ]);
    }

    public function store(StoreImeiModelRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        ImeiModel::query()->create([
            'make' => $validated['make'],
            'model' => trim($validated['model']),
            'serial' => isset($validated['serial']) ? trim((string) $validated['serial']) : '',
        ]);

        return redirect()
            ->route('settings.models.index', ['make' => $validated['make']])
            ->with('message', 'Model added.');
    }

    public function update(UpdateImeiModelRequest $request, ImeiModel $imeiModel): RedirectResponse
    {
        $validated = $request->validated();

        $imeiModel->update([
            'model' => trim($validated['model']),
            'serial' => isset($validated['serial']) ? trim((string) $validated['serial']) : '',
        ]);

        return redirect()
            ->route('settings.models.index', ['make' => $imeiModel->make])
            ->with('message', 'Model updated.');
    }

    public function destroy(ImeiModel $imeiModel): RedirectResponse
    {
        $make = $imeiModel->make;
        $imeiModel->delete();

        return redirect()
            ->route('settings.models.index', ['make' => $make])
            ->with('message', 'Model deleted.');
    }
}
