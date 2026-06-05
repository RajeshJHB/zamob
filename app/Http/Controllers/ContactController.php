<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Models\ContactCategory;
use App\Support\BrowseListLimit;
use App\Support\ContactCategoryFilter;
use App\Support\ContactPermissions;
use App\Support\ContactsTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function search(Request $request): View|RedirectResponse
    {
        $term = trim((string) $request->input('q', ''));
        $sort = ContactsTable::sortColumn($request->input('sort'));
        $dir = ContactsTable::sortDir($request->input('dir'));
        $selectedCategory = ContactCategoryFilter::resolveSelected($request->input('category'));

        $query = Contact::query()->with(['relatedContact', 'contactCategory']);
        ContactCategoryFilter::applyFilter($query, $selectedCategory);
        ContactsTable::applySort($query, $sort, $dir);

        $searchViewData = $this->searchViewData($sort, $dir, $selectedCategory);

        if ($term === '') {
            $contacts = $query
                ->limit(BrowseListLimit::limit())
                ->get();

            return view('contacts.search', [
                'term' => '',
                'contacts' => $contacts,
                'listingAll' => true,
                'showCreatePrompt' => false,
                'browseListLimit' => BrowseListLimit::limit(),
                ...$searchViewData,
            ]);
        }

        $contacts = $query->searchTerm($term)->get();

        if ($contacts->count() === 1) {
            return redirect()->route('contacts.show', $contacts->first());
        }

        if ($contacts->isEmpty()) {
            return view('contacts.search', [
                'term' => $term,
                'contacts' => $contacts,
                'listingAll' => false,
                'showCreatePrompt' => true,
                ...$searchViewData,
            ]);
        }

        return view('contacts.search', [
            'term' => $term,
            'contacts' => $contacts,
            'listingAll' => false,
            'showCreatePrompt' => false,
            ...$searchViewData,
        ]);
    }

    public function legacyContactsSearchRedirect(Request $request): RedirectResponse
    {
        return redirect()->route('contacts.index', $request->query());
    }

    public function create(Request $request): View
    {
        return view('contacts.form', [
            'contact' => null,
            'contactsForRelated' => Contact::query()->orderBy('surname')->orderBy('first_name')->get(),
            'contactCategories' => $this->contactCategories(),
            'defaultCategoryId' => ContactCategory::customerId(),
            'prefill' => [
                'telephone_1' => $request->input('telephone_1', ''),
            ],
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $contact = Contact::query()->create($this->contactAttributes($request));

        return redirect()
            ->route('contacts.show', $contact)
            ->with('message', 'Contact created. Add a service note below.');
    }

    public function show(Request $request, Contact $contact): View
    {
        $contact->load([
            'relatedContact',
            'contactCategory',
            'serviceNotes' => fn ($q) => $q
                ->with(['noteType', 'relatedNote', 'notesLinkingHere.noteType'])
                ->orderByDesc('noted_at')
                ->orderByDesc('id'),
        ]);

        $highlightNoteId = (int) $request->input('note', 0);

        return view('contacts.show', [
            'contact' => $contact,
            'canDeleteContact' => ContactPermissions::canDeleteContact($request->user(), $contact),
            'highlightNoteId' => $highlightNoteId > 0 ? $highlightNoteId : null,
        ]);
    }

    public function edit(Contact $contact): View
    {
        return view('contacts.form', [
            'contact' => $contact,
            'contactsForRelated' => Contact::query()
                ->where('id', '!=', $contact->id)
                ->orderBy('surname')
                ->orderBy('first_name')
                ->get(),
            'contactCategories' => $this->contactCategories(),
            'defaultCategoryId' => ContactCategory::customerId(),
            'prefill' => [],
        ]);
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        abort_unless(ContactPermissions::canEditContact($request->user(), $contact), 403);

        $contact->update($this->contactAttributes($request));

        return redirect()
            ->route('contacts.show', $contact)
            ->with('message', 'Contact updated.');
    }

    public function destroy(Request $request, Contact $contact): RedirectResponse
    {
        abort_unless(ContactPermissions::canDeleteContact($request->user(), $contact), 403);

        $contact->delete();

        return redirect()
            ->route('contacts.index')
            ->with('message', 'Contact deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function searchViewData(string $sort, string $dir, string|int $selectedCategory): array
    {
        $categories = $this->contactCategories();

        return [
            'sort' => $sort,
            'sortDir' => $dir,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory,
            'categoryUrlParam' => ContactCategoryFilter::urlParam($selectedCategory),
            'selectedCategoryLabel' => $this->selectedCategoryLabel($selectedCategory, $categories),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ContactCategory>  $categories
     */
    private function selectedCategoryLabel(string|int $selectedCategory, $categories): string
    {
        if ($selectedCategory === ContactCategoryFilter::ALL) {
            return 'All categories';
        }

        $match = $categories->firstWhere('id', $selectedCategory);

        return $match !== null ? $match->name : 'Contacts';
    }

    /**
     * @return \Illuminate\Support\Collection<int, ContactCategory>
     */
    private function contactCategories()
    {
        return ContactCategory::query()->orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function contactAttributes(StoreContactRequest|UpdateContactRequest $request): array
    {
        $categoryId = (int) $request->input('contact_category_id');
        if ($categoryId === 0) {
            $categoryId = ContactCategory::customerId();
        }

        return [
            'company_name' => trim((string) $request->input('company_name', '')),
            'first_name' => trim((string) $request->input('first_name', '')),
            'surname' => trim((string) $request->input('surname', '')),
            'telephone_1' => trim((string) $request->input('telephone_1', '')),
            'telephone_2' => trim((string) $request->input('telephone_2', '')),
            'email_address' => trim((string) $request->input('email_address', '')),
            'physical_address' => trim((string) $request->input('physical_address', '')),
            'related_contact_id' => $request->input('related_contact_id') ?: null,
            'contact_category_id' => $categoryId,
        ];
    }
}
