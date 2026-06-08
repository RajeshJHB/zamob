<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDefaultSettingsRequest;
use App\Models\AppSetting;
use App\Support\BrowseListLimit;
use App\Support\ImeiInShopAgeColour;
use App\Support\ImeiInShopAgeHighlightSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DefaultSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $ageSettings = ImeiInShopAgeHighlightSettings::load();

        return view('settings.default.index', [
            'browseListLimit' => BrowseListLimit::limit(),
            'ageSettings' => $ageSettings,
            'ageColourOptions' => ImeiInShopAgeColour::options(),
            'canEditDefaultSettings' => $request->user()?->canDeleteImeiReferenceData() === true,
        ]);
    }

    public function update(UpdateDefaultSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        AppSetting::setBrowseListLimit((int) $validated['browse_list_limit']);
        ImeiInShopAgeHighlightSettings::fromValidated($validated)->persist();

        return redirect()
            ->route('settings.default.index')
            ->with('message', 'Default settings saved.');
    }
}
