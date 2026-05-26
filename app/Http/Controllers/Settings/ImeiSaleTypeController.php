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
    public function index(): View
    {
        $saleTypes = ImeiSaleType::query()->orderBy('sale_type')->get();

        return view('settings.sale-types.index', [
            'saleTypes' => $saleTypes,
        ]);
    }

    public function store(StoreImeiSaleTypeRequest $request): RedirectResponse
    {
        ImeiSaleType::query()->create([
            'sale_type' => trim($request->validated('sale_type')),
        ]);

        return redirect()
            ->route('settings.sale-types.index')
            ->with('message', 'Sale type added.');
    }

    public function update(UpdateImeiSaleTypeRequest $request, ImeiSaleType $imeiSaleType): RedirectResponse
    {
        $imeiSaleType->update([
            'sale_type' => trim($request->validated('sale_type')),
        ]);

        return redirect()
            ->route('settings.sale-types.index')
            ->with('message', 'Sale type updated.');
    }

    public function destroy(Request $request, ImeiSaleType $imeiSaleType): RedirectResponse
    {
        abort_unless($request->user()->canDeleteImeiReferenceData(), 403);

        $imeiSaleType->delete();

        return redirect()
            ->route('settings.sale-types.index')
            ->with('message', 'Sale type deleted.');
    }
}
