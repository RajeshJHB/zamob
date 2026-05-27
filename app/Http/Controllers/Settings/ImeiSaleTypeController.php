<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreImeiSaleTypeRequest;
use App\Http\Requests\UpdateImeiSaleTypeRequest;
use App\Models\ImeiSaleType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImeiSaleTypeController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->canDeleteImeiReferenceData() === true, 403);

        $saleTypes = ImeiSaleType::query()->orderBy('sale_type')->get();

        return view('settings.sale-types.index', [
            'saleTypes' => $saleTypes,
        ]);
    }

    public function store(StoreImeiSaleTypeRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->canDeleteImeiReferenceData() === true, 403);

        ImeiSaleType::query()->create([
            'sale_type' => trim($request->validated('sale_type')),
        ]);

        return redirect()
            ->route('settings.sale-types.index')
            ->with('message', 'Sale type added.');
    }

    public function update(UpdateImeiSaleTypeRequest $request, ImeiSaleType $imeiSaleType): RedirectResponse
    {
        abort_unless($request->user()?->canDeleteImeiReferenceData() === true, 403);

        $imeiSaleType->update([
            'sale_type' => trim($request->validated('sale_type')),
        ]);

        return redirect()
            ->route('settings.sale-types.index')
            ->with('message', 'Sale type updated.');
    }

    public function destroy(Request $request, ImeiSaleType $imeiSaleType): RedirectResponse
    {
        abort_unless($request->user()?->canDeleteImeiReferenceData() === true, 403);

        $imeiSaleType->delete();

        return redirect()
            ->route('settings.sale-types.index')
            ->with('message', 'Sale type deleted.');
    }
}
