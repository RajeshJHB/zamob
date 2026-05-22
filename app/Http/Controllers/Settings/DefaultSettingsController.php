<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDefaultSettingsRequest;
use App\Models\AppSetting;
use App\Support\BrowseListLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DefaultSettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.default.index', [
            'browseListLimit' => BrowseListLimit::limit(),
        ]);
    }

    public function update(UpdateDefaultSettingsRequest $request): RedirectResponse
    {
        AppSetting::setBrowseListLimit((int) $request->validated('browse_list_limit'));

        return redirect()
            ->route('settings.default.index')
            ->with('message', 'Default settings saved.');
    }
}
