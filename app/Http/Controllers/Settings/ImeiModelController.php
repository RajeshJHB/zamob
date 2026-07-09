<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImeiModelRequest;
use App\Http\Requests\UpdateImeiModelRequest;
use App\Models\ImeiMake;
use App\Models\ImeiModel;
use App\Support\ImeiReferenceText;
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

        if ($selectedMake !== null && $selectedMake !== '') {
            $selectedMake = ImeiReferenceText::resolveStoredMake($selectedMake) ?? ImeiReferenceText::normalize($selectedMake);
        }

        if ($selectedMake === null || $selectedMake === '') {
            return view('settings.models.index', [
                'makes' => $makes,
                'selectedMake' => null,
                'models' => collect(),
            ]);
        }

        if (! ImeiReferenceText::makeExists($selectedMake)) {
            return redirect()
                ->route('settings.models.index')
                ->with('error', 'Please choose a valid make from the list.');
        }

        $models = ImeiReferenceText::applyWhereMake(ImeiModel::query(), $selectedMake)
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
        $make = ImeiReferenceText::resolveStoredMake($validated['make']) ?? ImeiReferenceText::normalize($validated['make']);

        ImeiModel::query()->create([
            'make' => $make,
            'model' => ImeiReferenceText::normalize($validated['model']),
            'serial' => isset($validated['serial']) ? ImeiReferenceText::normalize((string) $validated['serial']) : '',
            'item_code' => isset($validated['item_code']) ? ImeiReferenceText::normalize((string) $validated['item_code']) : '',
        ]);

        return redirect()
            ->route('settings.models.index', ['make' => $make])
            ->with('message', 'Model added.');
    }

    public function update(UpdateImeiModelRequest $request, ImeiModel $imeiModel): RedirectResponse
    {
        $validated = $request->validated();

        $imeiModel->update([
            'model' => ImeiReferenceText::normalize($validated['model']),
            'serial' => isset($validated['serial']) ? ImeiReferenceText::normalize((string) $validated['serial']) : '',
            'item_code' => isset($validated['item_code']) ? ImeiReferenceText::normalize((string) $validated['item_code']) : '',
        ]);

        return redirect()
            ->route('settings.models.index', ['make' => $imeiModel->make])
            ->with('message', 'Model updated.');
    }

    public function destroy(Request $request, ImeiModel $imeiModel): RedirectResponse
    {
        abort_unless($request->user()->canDeleteImeiReferenceData(), 403);

        $make = $imeiModel->make;
        $imeiModel->delete();

        return redirect()
            ->route('settings.models.index', ['make' => $make])
            ->with('message', 'Model deleted.');
    }
}
