<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactCategoryRequest;
use App\Http\Requests\UpdateContactCategoryRequest;
use App\Models\ContactCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ContactCategory::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('settings.contact-categories.index', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreContactCategoryRequest $request): RedirectResponse
    {
        $maxOrder = (int) ContactCategory::query()->max('sort_order');

        ContactCategory::query()->create([
            'name' => trim($request->validated('name')),
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()
            ->route('settings.contact-categories.index')
            ->with('message', 'Contact category added.');
    }

    public function update(UpdateContactCategoryRequest $request, ContactCategory $contactCategory): RedirectResponse
    {
        $contactCategory->update([
            'name' => trim($request->validated('name')),
        ]);

        return redirect()
            ->route('settings.contact-categories.index')
            ->with('message', 'Contact category updated.');
    }

    public function destroy(Request $request, ContactCategory $contactCategory): RedirectResponse
    {
        abort_unless($request->user()->canDeleteImeiReferenceData(), 403);

        abort_if($contactCategory->contacts()->exists(), 403, 'Cannot delete a contact category that is used on contacts.');

        $contactCategory->delete();

        return redirect()
            ->route('settings.contact-categories.index')
            ->with('message', 'Contact category deleted.');
    }
}
