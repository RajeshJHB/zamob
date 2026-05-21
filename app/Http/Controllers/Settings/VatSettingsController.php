<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVatSettingsRequest;
use App\Models\AppSetting;
use App\Support\ImeiCostIncl;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VatSettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.vat.index', [
            'vatPercent' => ImeiCostIncl::vatPercent(),
        ]);
    }

    public function update(UpdateVatSettingsRequest $request): RedirectResponse
    {
        AppSetting::setVatPercent((float) $request->validated('vat_percent'));

        return redirect()
            ->route('settings.vat.index')
            ->with('message', 'VAT percentage saved.');
    }
}
