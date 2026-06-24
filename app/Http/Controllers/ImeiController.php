<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkChangeImeiStatusRequest;
use App\Http\Requests\BulkEditImeiRequest;
use App\Http\Requests\StoreImeiRequest;
use App\Http\Requests\UpdateImeiRequest;
use App\Models\Contact;
use App\Models\Imei;
use App\Models\ImeiFilter;
use App\Models\ImeiLocation;
use App\Models\ImeiMake;
use App\Models\ImeiModel;
use App\Models\ImeiSaleType;
use App\Models\ImeiStatus;
use App\Models\ImeiType;
use App\Models\ServiceNote;
use App\Support\BrowseListLimit;
use App\Support\CashDevicesTable;
use App\Support\ContactImeiCustomerDetails;
use App\Support\ImeiBulkEdit;
use App\Support\ImeiCostIncl;
use App\Support\ImeiCsvExporter;
use App\Support\ImeiDeletedStatus;
use App\Support\ImeiFieldFilter;
use App\Support\ImeiLinkedServiceNote;
use App\Support\ImeiNormalizedLookup;
use App\Support\ImeiStaffAudit;
use App\Support\ImeiTextLimits;
use App\Support\ImeiValidator;
use App\Support\ServiceNoteDealDetails;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImeiController extends Controller
{
    private const ACTIVE_PROFILE_SESSION_KEY = 'imeis.active_profile_id';

    private const PREFER_NO_PROFILE_SESSION_KEY = 'imeis.prefer_no_profile';

    /**
     * Query keys allowed when returning from view/edit to the IMEI results table.
     *
     * @var list<string>
     */
    private const INDEX_RETURN_QUERY_KEYS = [
        'profile_id',
        'scope',
        'columns',
        'date_scope',
        'date_column',
        'start_date',
        'end_date',
        'search',
        'search2',
        'sort1_column',
        'sort1_dir',
        'sort2_column',
        'sort2_dir',
        'field_filter_1',
        'field_value_1',
        'field_not_1',
        'field_filter_2',
        'field_value_2',
        'field_not_2',
        'field_filter_3',
        'field_value_3',
        'field_not_3',
        'page',
    ];

    public const DATE_COLUMNS = [
        'date_in' => 'Date In',
        'date_updated' => 'Date Updated',
    ];

    /** @var list<string> */
    public const VIRTUAL_COLUMNS = [
        'cost_incl',
    ];

    public const COLUMNS = [
        'id' => 'ID',
        'date_in' => 'Date in',
        'cash_stock_type' => 'Sale type',
        'date_updated' => 'Date updated',
        'make' => 'Make',
        'model' => 'Model',
        'imei' => 'IMEI',
        'sn' => 'Serial Number',
        'location' => 'Location',
        'type' => 'Type',
        'status' => 'Status',
        'notes' => 'Customer Details',
        'phonenumber' => 'Deal Phone Number',
        'ref' => 'Deal Details',
        'staff' => 'Staff',
        'item_code' => 'Item code',
        'ourON' => 'ourON',
        'salesON' => 'salesON',
        'cost_excl' => 'Cost excl',
        'cost_incl' => 'Cost incl',
        'selling_price' => 'Selling price',
    ];

    /**
     * @return array<string, string>
     */
    public static function databaseColumns(): array
    {
        return array_diff_key(self::COLUMNS, array_flip(self::VIRTUAL_COLUMNS));
    }

    public function create(Request $request): View
    {
        $viewRecord = null;
        if ($id = session()->pull('imei_view_id')) {
            $imei = Imei::query()->visibleTo(auth()->user())->find($id);
            if ($imei !== null) {
                $viewRecord = $this->imeiRecordForLookup($imei);
            }
        }

        return $this->imeiFormView(
            viewRecord: $viewRecord,
            createPageHeading: null,
            createPageIntro: null,
            defaultImeiNonStandard: null,
            returnQuery: null,
            embedded: $request->boolean('embedded'),
            embeddedClose: $request->boolean('close'),
        );
    }

    public function edit(Request $request, Imei $imei): View
    {
        $this->abortIfDeletedAndNotAuthorized($request, $imei);

        $digits = ImeiValidator::normalizeDigits($imei->imei);
        $returnQuery = $this->returnQueryStringFromRequest($request);

        return $this->imeiFormView(
            viewRecord: $this->imeiRecordForLookup($imei),
            createPageHeading: 'Edit IMEI',
            createPageIntro: 'Update the record below and choose Save.',
            defaultImeiNonStandard: ImeiValidator::isValidChecksum($digits) ? '0' : '1',
            returnQuery: $returnQuery,
            embedded: $request->boolean('embedded'),
            embeddedClose: $request->boolean('close'),
            editableOnLoad: true,
        );
    }

    public function receipt(Request $request, Imei $imei): View
    {
        $this->abortIfDeletedAndNotAuthorized($request, $imei);

        return view('imeis.receipt', [
            'imei' => $imei,
        ]);
    }

    /**
     * Serves the receipt header image from resources/ so updates to the logo file are used without copying to public/.
     */
    public function receiptLogo(): BinaryFileResponse
    {
        $paths = [
            resource_path('VodacomLogo.png'),
            resource_path('Vodacomlogo.png'),
            resource_path('vodacomlogo.png'),
            resource_path('Vodacom.jpg'),
            resource_path('vodacom.jpg'),
        ];

        foreach ($paths as $path) {
            if (! is_file($path)) {
                continue;
            }

            $contentType = str_ends_with(strtolower($path), '.png')
                ? 'image/png'
                : 'image/jpeg';

            return response()->file($path, [
                'Content-Type' => $contentType,
                'Cache-Control' => 'private, max-age=3600',
            ]);
        }

        abort(404, 'Receipt logo image not found in resources/.');
    }

    public function lastForCopy(): JsonResponse
    {
        $imei = Imei::query()->visibleTo(auth()->user())->orderByDesc('id')->first();

        if ($imei === null) {
            return response()->json([
                'record' => null,
                'message' => 'There are no IMEI records to copy yet.',
            ]);
        }

        return response()->json([
            'record' => $this->imeiRecordForLookup($imei),
        ]);
    }

    public function browseContacts(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        $query = Contact::query();
        if ($term !== '') {
            $query->searchTerm($term);
        }

        $contacts = $query
            ->orderBy('company_name')
            ->orderBy('surname')
            ->orderBy('first_name')
            ->orderBy('id')
            ->get();

        return response()->json([
            'contacts' => $contacts->map(fn (Contact $contact): array => [
                'id' => $contact->id,
                'company_name' => $contact->company_name,
                'first_name' => $contact->first_name,
                'surname' => $contact->surname,
                'telephone_1' => $contact->telephone_1,
                'customer_details' => ContactImeiCustomerDetails::format($contact),
            ])->values()->all(),
        ]);
    }

    public function browseContactServiceNotes(Contact $contact): JsonResponse
    {
        $notes = ServiceNote::query()
            ->with('noteType')
            ->where('contact_id', $contact->id)
            ->orderByDesc('noted_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'contact_id' => $contact->id,
            'notes' => $notes->map(fn (ServiceNote $note): array => [
                'id' => $note->id,
                'note_number' => $note->formattedNoteNumber(),
                'note_type' => $note->noteType?->name,
                'heading' => $note->heading,
                'noted_at' => $note->noted_at?->format('Y-m-d H:i'),
                'body' => $note->body,
                'status' => $note->status,
                'deal_details_text' => ServiceNoteDealDetails::format($note),
            ])->values()->all(),
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $raw = trim((string) $request->query('imei', ''));
        $normalized = ImeiValidator::normalizeDigits($raw);
        $nonStandard = $request->boolean('non_standard');

        if ($nonStandard) {
            $normalizedNs = ImeiValidator::normalizeNonStandard($raw);
            $len = strlen($normalizedNs);
            if ($len < 1) {
                return response()->json([
                    'valid' => false,
                    'exists' => false,
                    'canonical_imei' => null,
                    'record' => null,
                    'non_standard' => true,
                    'message' => 'Enter a non-standard IMEI (spaces, dashes, and slashes are ignored for matching).',
                ]);
            }
            if ($len > ImeiValidator::MAX_NON_STANDARD_IMEI_LENGTH) {
                return response()->json([
                    'valid' => false,
                    'exists' => false,
                    'canonical_imei' => null,
                    'record' => null,
                    'non_standard' => true,
                    'message' => 'Non-standard IMEI is too long (maximum '.ImeiValidator::MAX_NON_STANDARD_IMEI_LENGTH.' characters after removing spaces, dashes, and slashes).',
                ]);
            }

            $record = ImeiNormalizedLookup::find($normalizedNs);

            return $this->lookupJsonResponse($request, $record, $normalizedNs, true);
        }

        if (! ImeiValidator::isValidChecksum($normalized)) {
            return response()->json([
                'valid' => false,
                'exists' => false,
                'deleted' => false,
                'canonical_imei' => strlen($normalized) === 15 ? $normalized : null,
                'record' => null,
                'non_standard' => false,
                'message' => 'Enter a valid 15-digit IMEI (check digit must be correct).',
            ]);
        }

        $record = ImeiNormalizedLookup::find($normalized);

        return $this->lookupJsonResponse($request, $record, $normalized, false);
    }

    private function lookupJsonResponse(Request $request, ?Imei $record, string $canonicalImei, bool $nonStandard): JsonResponse
    {
        if ($record === null) {
            return response()->json([
                'valid' => true,
                'exists' => false,
                'deleted' => false,
                'canonical_imei' => $canonicalImei,
                'record' => null,
                'non_standard' => $nonStandard,
            ]);
        }

        if (ImeiDeletedStatus::isDeleted($record) && ! ImeiDeletedStatus::userCanViewDeleted($request->user())) {
            return response()->json([
                'valid' => true,
                'exists' => true,
                'deleted' => true,
                'canonical_imei' => $canonicalImei,
                'record' => null,
                'non_standard' => $nonStandard,
                'message' => ImeiNormalizedLookup::DELETED_IMEI_MESSAGE,
            ]);
        }

        if (! ImeiDeletedStatus::isDeleted($record)) {
            $visibleRecord = Imei::query()
                ->visibleTo($request->user())
                ->whereKey($record->id)
                ->first();

            if ($visibleRecord === null) {
                return response()->json([
                    'valid' => true,
                    'exists' => false,
                    'deleted' => false,
                    'canonical_imei' => $canonicalImei,
                    'record' => null,
                    'non_standard' => $nonStandard,
                ]);
            }

            $record = $visibleRecord;
        }

        return response()->json([
            'valid' => true,
            'exists' => true,
            'deleted' => ImeiDeletedStatus::isDeleted($record),
            'canonical_imei' => $canonicalImei,
            'record' => $this->imeiRecordForLookup($record),
            'non_standard' => $nonStandard,
            'message' => ImeiDeletedStatus::isDeleted($record)
                ? ImeiNormalizedLookup::DELETED_IMEI_ROLE4_MESSAGE
                : null,
        ]);
    }

    public function store(StoreImeiRequest $request): RedirectResponse
    {
        $data = ImeiLinkedServiceNote::stripFromImeiPayload($request->validated());
        $data['date_in'] = now();
        $data['date_updated'] = now();
        $data['staff'] = ImeiStaffAudit::appendEmail('', (string) $request->user()->email);
        $imei = Imei::create($data);

        ImeiLinkedServiceNote::applyFromImeiSave($request, $imei);

        return $this->redirectAfterImeiUpdate(
            $imei,
            $this->returnQueryStringFromRequest($request),
            'IMEI record created.',
            $request->boolean('embedded'),
        );
    }

    public function update(UpdateImeiRequest $request, Imei $imei): RedirectResponse
    {
        $data = ImeiLinkedServiceNote::stripFromImeiPayload($request->validated());
        $data['date_updated'] = now();
        $data['staff'] = ImeiStaffAudit::appendEmail((string) $imei->staff, (string) $request->user()->email);
        $imei->update($data);

        ImeiLinkedServiceNote::applyFromImeiSave($request, $imei->fresh() ?? $imei);

        return $this->redirectAfterImeiUpdate(
            $imei,
            $this->returnQueryStringFromRequest($request),
            'IMEI record updated.',
            $request->boolean('embedded'),
        );
    }

    public function destroy(Request $request, Imei $imei): RedirectResponse
    {
        abort_unless($request->user() !== null, 403);

        $returnQuery = $this->returnQueryStringFromRequest($request);

        if (! ImeiDeletedStatus::isDeleted($imei)) {
            $imei->update([
                'status' => ImeiDeletedStatus::VALUE,
                'date_updated' => now(),
                'staff' => ImeiStaffAudit::appendEmail((string) $imei->staff, (string) $request->user()->email),
            ]);
        }

        $returnListUrl = $this->indexUrlFromReturnQuery($returnQuery);
        if ($returnListUrl !== null) {
            return redirect()->to($returnListUrl)->with('message', 'IMEI record marked as deleted.');
        }

        if ($request->boolean('embedded')) {
            return redirect()
                ->route('imeis.create', ['embedded' => 1, 'close' => 1])
                ->with('message', 'IMEI record marked as deleted.');
        }

        return redirect()
            ->route('imeis.create')
            ->with('message', 'IMEI record marked as deleted.');
    }

    public function filter(Request $request): View
    {
        $user = $request->user();

        $savedFilters = ImeiFilter::query()
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('name')
            ->get();

        $activeProfile = $this->applyActiveFilterProfile($request, $savedFilters);
        $currentProfileName = $activeProfile?->name;
        $defaultProfileId = (int) ($savedFilters->firstWhere('is_default', true)?->id ?? 0);

        return view('imeis.filter', [
            'columns' => self::COLUMNS,
            'dateColumns' => self::DATE_COLUMNS,
            'oldDateScope' => $request->input('date_scope', 'all'),
            'oldDateColumn' => $request->input('date_column', 'date_in'),
            'oldStartDate' => $request->input('start_date'),
            'oldEndDate' => $request->input('end_date'),
            'oldScope' => $request->input('scope', 'all'),
            'oldColumns' => $request->input('columns', []),
            'oldSearch' => $request->input('search'),
            'oldSearch2' => $request->input('search2'),
            'oldSort1Column' => $request->input('sort1_column'),
            'oldSort1Dir' => $request->input('sort1_dir', 'asc'),
            'oldSort2Column' => $request->input('sort2_column'),
            'oldSort2Dir' => $request->input('sort2_dir', 'asc'),
            'fieldFilterRows' => collect(ImeiFieldFilter::FILTER_INDICES)
                ->map(fn (int $index): array => [
                    'index' => $index,
                    'field' => $request->input('field_filter_'.$index, ''),
                    'value' => $request->input('field_value_'.$index, ''),
                    'not' => $request->boolean(ImeiFieldFilter::excludeRequestKey($index)),
                ])
                ->all(),
            'fieldFilterPicklists' => ImeiFieldFilter::picklistOptions($user),
            'savedFilters' => $savedFilters,
            'currentProfileName' => $currentProfileName,
            'activeProfileId' => $request->session()->get(self::ACTIVE_PROFILE_SESSION_KEY),
            'defaultProfileId' => $defaultProfileId,
        ]);
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->resolveSearchProfileSwitch($request)) {
            return $redirect;
        }

        $user = $request->user();
        $this->applyDefaultFilterProfileIfNone($request);

        $savedFilters = ImeiFilter::query()
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('name')
            ->get();
        $activeProfileId = (int) $request->session()->get(self::ACTIVE_PROFILE_SESSION_KEY, 0);

        $dateScope = $request->input('date_scope', 'all');
        $dateColumn = $request->input('date_column');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($dateScope === 'range' && $startDate && $endDate && $startDate > $endDate) {
            return redirect()
                ->route('imeis.filter', $this->imeiFilterParams($request))
                ->with('error', 'The end date must be on or after the start date.');
        }

        $query = $this->buildImeiQuery($request);

        $statusFilterValue = ImeiFieldFilter::statusFilterValue($request);
        $roleFourWithStatusFilter = $request->user()?->canDeleteImeiReferenceData() === true
            && $statusFilterValue !== null;
        $bulkStatusCount = $roleFourWithStatusFilter ? (clone $query)->count() : 0;
        $canBulkChangeStatus = $roleFourWithStatusFilter && $bulkStatusCount > 1;

        $canBulkEditImei = $request->user()?->canBulkEditImei() === true;
        $bulkEditCount = $canBulkEditImei ? (clone $query)->count() : 0;

        $filteredCostInclTotal = null;
        if (! $this->isUnfilteredBrowse($request)) {
            $filteredCostInclTotal = ImeiCostIncl::formatTotal(
                ImeiCostIncl::sumInclusive((clone $query)->pluck('cost_excl'))
            );
        }

        $imeis = $query->paginate(25)->withQueryString();

        $selectedColumns = $this->selectedColumnsFromRequest($request);

        return view('imeis.index', [
            'imeis' => $imeis,
            'columns' => $selectedColumns,
            'columnLabels' => self::COLUMNS,
            'filterParams' => $this->imeiFilterParams($request),
            'canBulkChangeStatus' => $canBulkChangeStatus,
            'canBulkEditImei' => $canBulkEditImei,
            'statusFilterValue' => $statusFilterValue,
            'bulkStatusCount' => $bulkStatusCount,
            'bulkEditCount' => $bulkEditCount,
            'statusOptions' => ImeiStatus::query()->orderBy('status')->pluck('status')->all(),
            'saleTypeOptions' => ImeiSaleType::query()->orderBy('sale_type')->pluck('sale_type')->all(),
            'typeOptions' => ImeiType::query()->orderBy('type')->pluck('type')->all(),
            'currentProfileName' => $this->activeProfileName($request),
            'savedFilters' => $savedFilters,
            'activeProfileId' => $activeProfileId,
            'filteredCostInclTotal' => $filteredCostInclTotal,
        ]);
    }

    public function print(Request $request): View
    {
        $query = $this->buildImeiQuery($request);
        $imeis = $query->get();

        return view('imeis.print', [
            'imeis' => $imeis,
            'columns' => $this->selectedColumnsFromRequest($request),
            'columnLabels' => self::COLUMNS,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->buildImeiQuery($request, unlimited: true);

        return ImeiCsvExporter::download(
            $query->cursor(),
            $this->selectedColumnsFromRequest($request),
            self::COLUMNS,
        );
    }

    /**
     * @return list<string>
     */
    private function selectedColumnsFromRequest(Request $request): array
    {
        $selectedColumns = $request->input('columns');
        if (is_string($selectedColumns)) {
            $selectedColumns = array_filter(explode(',', $selectedColumns));
        }

        if (empty($selectedColumns) || $request->input('scope') === 'all') {
            return array_keys(self::COLUMNS);
        }

        $selectedColumns = array_values(array_intersect($selectedColumns, array_keys(self::COLUMNS)));

        return $selectedColumns === [] ? array_keys(self::COLUMNS) : $selectedColumns;
    }

    public function bulkChangeStatus(BulkChangeImeiStatusRequest $request): RedirectResponse
    {
        $fromStatus = ImeiFieldFilter::statusFilterValue($request);
        $toStatus = (string) $request->validated('status_to');
        $userEmail = (string) $request->user()->email;
        $now = now();

        $query = $this->buildImeiQueryForBulkOperation($request);
        $updated = $this->processImeisForBulkOperation($query, function (Imei $imei) use ($toStatus, $userEmail, $now): void {
            $imei->update([
                'status' => $toStatus,
                'date_updated' => $now,
                'staff' => ImeiStaffAudit::appendEmail((string) $imei->staff, $userEmail),
            ]);
        });

        return redirect()
            ->route('imeis.index', $this->imeiFilterParams($request))
            ->with('message', "Updated {$updated} record(s) from {$fromStatus} to {$toStatus}.");
    }

    public function bulkEdit(BulkEditImeiRequest $request): RedirectResponse
    {
        $userEmail = (string) $request->user()->email;
        $now = now();
        $formValues = $request->all();

        $query = $this->buildImeiQueryForBulkOperation($request);
        ImeiBulkEdit::applySearchCriteria($query, $request->searchCriteria());

        $removeFieldKeys = ImeiBulkEdit::removeSearchTextFieldKeys($formValues);
        $replaceSearchFieldKeys = ImeiBulkEdit::replaceSearchTextFieldKeys($formValues);
        $partialTextFieldKeys = ImeiBulkEdit::partialTextEditFieldKeys($formValues);
        $updateAttributes = ImeiBulkEdit::buildUpdateAttributes(
            $request->replaceValues(),
            $now,
            $partialTextFieldKeys,
            ImeiBulkEdit::shouldKeepDateUpdated($formValues),
            ImeiBulkEdit::normalizedReplaceDateUpdated($formValues),
        );

        $updated = $this->processImeisForBulkOperation($query, function (Imei $imei) use (
            $updateAttributes,
            $formValues,
            $removeFieldKeys,
            $replaceSearchFieldKeys,
            $userEmail,
        ): void {
            $attributes = $updateAttributes;

            foreach ($removeFieldKeys as $fieldKey) {
                $searchTerm = ImeiBulkEdit::searchValue($formValues, $fieldKey);
                if ($searchTerm === null) {
                    continue;
                }

                $column = ImeiBulkEdit::databaseColumn($fieldKey);
                $attributes[$column] = ImeiBulkEdit::removeSearchTextFromField(
                    (string) $imei->{$column},
                    $searchTerm,
                );
            }

            foreach ($replaceSearchFieldKeys as $fieldKey) {
                $searchTerm = ImeiBulkEdit::searchValue($formValues, $fieldKey);
                $replaceWith = ImeiBulkEdit::replaceValue($formValues, $fieldKey);
                if ($searchTerm === null || $replaceWith === null) {
                    continue;
                }

                $column = ImeiBulkEdit::databaseColumn($fieldKey);
                $attributes[$column] = ImeiBulkEdit::replaceSearchTextInField(
                    (string) $imei->{$column},
                    $searchTerm,
                    $replaceWith,
                );
            }

            $imei->update([
                ...$attributes,
                'staff' => ImeiStaffAudit::appendEmail((string) $imei->staff, $userEmail),
            ]);
        });

        return redirect()
            ->route('imeis.index', $this->imeiFilterParams($request))
            ->with('message', "Bulk edit updated {$updated} record(s).");
    }

    private function buildImeiQueryForBulkOperation(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $this->applyActiveFilterProfile($request);

        return $this->buildImeiQuery($request);
    }

    /**
     * @param  callable(Imei): void  $callback
     */
    private function processImeisForBulkOperation(\Illuminate\Database\Eloquent\Builder $query, callable $callback): int
    {
        $ids = (clone $query)->reorder('id')->pluck('id');
        $updated = 0;

        foreach ($ids->chunk(100) as $idChunk) {
            $imeis = Imei::query()
                ->whereIn('id', $idChunk->all())
                ->orderBy('id')
                ->get();

            foreach ($imeis as $imei) {
                $callback($imei);
                $updated++;
            }
        }

        return $updated;
    }

    private function buildImeiQuery(Request $request, bool $unlimited = false): \Illuminate\Database\Eloquent\Builder
    {
        $dateScope = $request->input('date_scope', 'all');
        $dateColumn = $request->input('date_column');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Imei::query();
        ImeiDeletedStatus::applyVisibleScope($query, $request->user());

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                foreach (array_keys(self::databaseColumns()) as $column) {
                    $q->orWhere($column, 'LIKE', $term);
                }
            });
        }

        $search2 = trim((string) $request->input('search2', ''));
        if ($search2 !== '') {
            $term2 = '%'.$search2.'%';
            $query->where(function ($q) use ($term2) {
                foreach (array_keys(self::databaseColumns()) as $column) {
                    $q->orWhere($column, 'LIKE', $term2);
                }
            });
        }

        if ($dateScope === 'range' && $dateColumn && in_array($dateColumn, array_keys(self::DATE_COLUMNS), true) && $startDate && $endDate) {
            $query->whereBetween($dateColumn, [
                $startDate.' 00:00:00',
                $endDate.' 23:59:59',
            ]);
        }

        ImeiFieldFilter::applyToQuery($query, $request);

        if (! $unlimited && $this->isUnfilteredBrowse($request)) {
            // Pluck IDs first: MySQL (older versions) rejects LIMIT inside IN subqueries.
            $latestIds = Imei::query()
                ->visibleTo($request->user())
                ->orderByDesc('date_updated')
                ->orderByDesc('date_in')
                ->orderByDesc('id')
                ->limit(BrowseListLimit::limit())
                ->pluck('id');

            $query->whereIn('id', $latestIds);
        }

        // Sorting: up to two levels based on selected columns.
        $allowedColumns = array_keys(self::databaseColumns());

        $sort1Column = $request->input('sort1_column');
        $sort1Dir = strtolower((string) $request->input('sort1_dir', 'asc'));
        $sort2Column = $request->input('sort2_column');
        $sort2Dir = strtolower((string) $request->input('sort2_dir', 'asc'));

        $hasSort = false;

        if ($sort1Column && in_array($sort1Column, $allowedColumns, true)) {
            if (! in_array($sort1Dir, ['asc', 'desc'], true)) {
                $sort1Dir = 'asc';
            }
            $query->orderBy($sort1Column, $sort1Dir);
            $hasSort = true;
        }

        if ($sort2Column && in_array($sort2Column, $allowedColumns, true) && $sort2Column !== $sort1Column) {
            if (! in_array($sort2Dir, ['asc', 'desc'], true)) {
                $sort2Dir = 'asc';
            }
            $query->orderBy($sort2Column, $sort2Dir);
            $hasSort = true;
        }

        // Default sort if nothing chosen: newest Date updated, then newest Date In.
        if (! $hasSort) {
            $query->orderByDesc('date_updated');
            $query->orderByDesc('date_in');
        }

        return $query;
    }

    /**
     * True when the request has no search criteria (column display choices do not count).
     */
    private function isUnfilteredBrowse(Request $request): bool
    {
        if (trim((string) $request->input('search', '')) !== '') {
            return false;
        }

        if (trim((string) $request->input('search2', '')) !== '') {
            return false;
        }

        if ($request->input('date_scope', 'all') === 'range') {
            return false;
        }

        if ($request->filled('sort1_column') || $request->filled('sort2_column')) {
            return false;
        }

        if (ImeiFieldFilter::hasActive($request)) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function imeiFilterParams(Request $request): array
    {
        return $request->only([
            'profile_id',
            'scope',
            'columns',
            'date_scope',
            'date_column',
            'start_date',
            'end_date',
            'search',
            'search2',
            'sort1_column',
            'sort1_dir',
            'sort2_column',
            'sort2_dir',
            ...ImeiFieldFilter::queryKeys(),
        ]);
    }

    public function saveFilter(Request $request): RedirectResponse
    {
        $request->validate([
            'profile_name' => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        // Take all current filter inputs (from the filter form) except meta fields.
        $params = $request->except([
            '_token',
            'profile_name',
            'from_filter',
        ]);

        $name = $request->input('profile_name');

        $filter = ImeiFilter::query()
            ->where('user_id', $user->id)
            ->where('name', $name)
            ->first();

        if ($filter) {
            $filter->update([
                'params' => $params,
            ]);
        } else {
            $filter = ImeiFilter::create([
                'user_id' => $user->id,
                'name' => $name,
                'params' => $params,
            ]);
        }

        $request->session()->put(self::ACTIVE_PROFILE_SESSION_KEY, (int) $filter->id);
        $request->session()->forget(self::PREFER_NO_PROFILE_SESSION_KEY);

        return redirect()
            ->route('imeis.filter', array_merge($params, [
                'profile_id' => optional($filter ?? null)?->id,
            ]))
            ->with('message', 'Filter profile saved.');
    }

    public function applyFilter(Request $request, ImeiFilter $filter): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($filter->user_id !== null && (int) $filter->user_id !== (int) $user->id) {
            abort(403, 'You do not have permission to use this filter.');
        }

        $params = $filter->params ?? [];
        $params['profile_id'] = $filter->id;

        $request->session()->put(self::ACTIVE_PROFILE_SESSION_KEY, (int) $filter->id);
        $request->session()->forget(self::PREFER_NO_PROFILE_SESSION_KEY);

        return redirect()->route('imeis.filter', $params);
    }

    public function deleteFilter(Request $request, ImeiFilter $filter): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($filter->user_id !== null && (int) $filter->user_id !== (int) $user->id) {
            abort(403, 'You do not have permission to delete this filter.');
        }

        $filter->delete();

        if ((int) $request->session()->get(self::ACTIVE_PROFILE_SESSION_KEY) === (int) $filter->id) {
            $request->session()->forget(self::ACTIVE_PROFILE_SESSION_KEY);
        }

        return redirect()
            ->route('imeis.filter', $request->query())
            ->with('message', 'Filter profile deleted.');
    }

    public function clearFilterProfile(Request $request): RedirectResponse
    {
        $request->session()->forget(self::ACTIVE_PROFILE_SESSION_KEY);

        return redirect()->route('imeis.filter');
    }

    public function applyProfileFromIndex(Request $request, ImeiFilter $filter): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ((int) $filter->user_id !== (int) $user->id) {
            abort(403, 'You do not have permission to use this filter.');
        }

        $request->session()->put(self::ACTIVE_PROFILE_SESSION_KEY, (int) $filter->id);
        $request->session()->forget(self::PREFER_NO_PROFILE_SESSION_KEY);

        return redirect()->route('imeis.index');
    }

    public function clearProfileFromIndex(Request $request): RedirectResponse
    {
        $request->session()->forget(self::ACTIVE_PROFILE_SESSION_KEY);
        $request->session()->put(self::PREFER_NO_PROFILE_SESSION_KEY, true);

        return redirect()->route('imeis.index');
    }

    public function resetSearch(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        /** @var ImeiFilter|null $defaultProfile */
        $defaultProfile = ImeiFilter::query()
            ->where('user_id', $user->id)
            ->where('is_default', true)
            ->first();

        if ($defaultProfile !== null) {
            $previousActiveId = (int) $request->session()->get(self::ACTIVE_PROFILE_SESSION_KEY, 0);
            $request->session()->put(self::ACTIVE_PROFILE_SESSION_KEY, (int) $defaultProfile->id);
            $request->session()->forget(self::PREFER_NO_PROFILE_SESSION_KEY);

            $onDefaultProfile = $previousActiveId === (int) $defaultProfile->id
                || (int) $request->input('profile_id') === (int) $defaultProfile->id;

            if ($onDefaultProfile) {
                // Default profiles are columns-only: clear quick search text, keep column state.
                $params = $this->stripQuickSearchCriteria(array_merge(
                    $this->imeiFilterParams($request),
                    ['profile_id' => $defaultProfile->id],
                ));
            } else {
                $params = $this->defaultProfileIndexParams($defaultProfile);
            }

            return redirect()->route('imeis.index', $this->filterParamsForRedirect($params));
        }

        $request->session()->forget(self::ACTIVE_PROFILE_SESSION_KEY);
        $request->session()->put(self::PREFER_NO_PROFILE_SESSION_KEY, true);

        return redirect()->route('imeis.index', $this->filterParamsForRedirect(
            $this->defaultFilterSearchParams(),
        ));
    }

    public function updateDefaultFilterProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $profileId = $request->input('profile_id');
        $wantDefault = $request->boolean('is_default');

        if (! $wantDefault || $profileId === null || $profileId === '') {
            ImeiFilter::query()->where('user_id', $user->id)->update(['is_default' => false]);

            return redirect()
                ->route('imeis.filter')
                ->with('message', 'Default profile cleared.');
        }

        /** @var ImeiFilter|null $filter */
        $filter = ImeiFilter::query()
            ->where('user_id', $user->id)
            ->whereKey((int) $profileId)
            ->first();

        if ($filter === null) {
            return redirect()
                ->route('imeis.filter')
                ->with('error', 'Please select a valid profile.');
        }

        if (! $this->isColumnsOnlyProfileParams(is_array($filter->params) ? $filter->params : [])) {
            return redirect()
                ->route('imeis.filter', ['profile_id' => $filter->id])
                ->with('error', 'A default profile can only include selected columns (no other search criteria).');
        }

        ImeiFilter::query()->where('user_id', $user->id)->update(['is_default' => false]);
        $filter->update(['is_default' => true]);

        return redirect()
            ->route('imeis.filter', ['profile_id' => $filter->id])
            ->with('message', 'Default profile saved.');
    }

    private function applyDefaultFilterProfileIfNone(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            return;
        }

        if ($request->session()->get(self::PREFER_NO_PROFILE_SESSION_KEY) === true) {
            return;
        }

        if ($request->filled('profile_id')) {
            return;
        }

        if ((int) $request->session()->get(self::ACTIVE_PROFILE_SESSION_KEY, 0) !== 0) {
            return;
        }

        $defaultProfileId = ImeiFilter::query()
            ->where('user_id', $user->id)
            ->where('is_default', true)
            ->value('id');

        if (! $defaultProfileId) {
            return;
        }

        $request->session()->put(self::ACTIVE_PROFILE_SESSION_KEY, (int) $defaultProfileId);
    }

    /**
     * Default profiles may only contain column selections (no search/date/sort/field filters).
     *
     * @param  array<string, mixed>  $params
     */
    private function isColumnsOnlyProfileParams(array $params): bool
    {
        $scope = (string) ($params['scope'] ?? '');
        $columns = $params['columns'] ?? null;

        if ($scope !== 'selected') {
            return false;
        }

        if (! is_array($columns) || $columns === []) {
            return false;
        }

        if (trim((string) ($params['search'] ?? '')) !== '') {
            return false;
        }

        if (trim((string) ($params['search2'] ?? '')) !== '') {
            return false;
        }

        if (($params['date_scope'] ?? 'all') === 'range') {
            return false;
        }

        if (! empty($params['sort1_column']) || ! empty($params['sort2_column'])) {
            return false;
        }

        foreach (ImeiFieldFilter::queryKeys() as $key) {
            if (trim((string) ($params[$key] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * When Search is submitted from the filter form, load the chosen profile (or clear for none)
     * before searching so stale form values from a previous profile are not kept.
     */
    private function resolveSearchProfileSwitch(Request $request): ?RedirectResponse
    {
        if (! $request->boolean('from_filter')) {
            if ($request->boolean('quick_search')) {
                $this->applySessionProfileForQuickSearch($request);
            } else {
                $this->applyActiveFilterProfile($request);
            }

            return null;
        }

        $user = $request->user();
        if (! $user) {
            return null;
        }

        $activeId = (int) $request->session()->get(self::ACTIVE_PROFILE_SESSION_KEY, 0);
        $submitted = $request->input('profile_id');
        $submittedId = ($submitted === null || $submitted === '') ? 0 : (int) $submitted;

        if ($submittedId === 0) {
            $request->session()->forget(self::ACTIVE_PROFILE_SESSION_KEY);
            $request->session()->put(self::PREFER_NO_PROFILE_SESSION_KEY, true);
            $params = $this->defaultFilterSearchParams();
        } else {
            /** @var ImeiFilter|null $filter */
            $filter = ImeiFilter::query()
                ->where('user_id', $user->id)
                ->whereKey($submittedId)
                ->first();

            if (! $filter) {
                $request->session()->forget(self::ACTIVE_PROFILE_SESSION_KEY);
                $params = $this->defaultFilterSearchParams();
            } else {
                $request->session()->put(self::ACTIVE_PROFILE_SESSION_KEY, $submittedId);
                $request->session()->forget(self::PREFER_NO_PROFILE_SESSION_KEY);
                $params = $this->sanitizeFilterProfileParams(
                    is_array($filter->params) ? $filter->params : [],
                );
                $params['profile_id'] = $filter->id;
            }
        }

        $formParams = $this->sanitizeFilterProfileParams($this->imeiFilterParams($request));
        unset($formParams['profile_id']);

        if ($submittedId !== 0 && $submittedId === $activeId) {
            $params = array_merge($params, $formParams);
        } elseif ($submittedId === 0) {
            $params = array_merge(
                $params,
                $this->searchSortOverridesFromForm($request),
                $this->filterCriteriaOverridesFromForm($request),
            );
        }

        $redirectParams = $this->filterParamsForRedirect($params);

        if ($this->filterSearchRedirectMatchesRequest($request, $redirectParams)) {
            $request->replace(array_merge($request->query(), $redirectParams));
            $request->query->replace(array_merge($request->query(), $redirectParams));

            return null;
        }

        return redirect()->route('imeis.index', $redirectParams);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFilterSearchParams(): array
    {
        return [
            'scope' => 'all',
            'date_scope' => 'all',
        ];
    }

    /**
     * Default profiles may only store column selections (see isColumnsOnlyProfileParams).
     *
     * @return array<string, mixed>
     */
    private function defaultProfileIndexParams(ImeiFilter $defaultProfile): array
    {
        $saved = $this->sanitizeFilterProfileParams(
            is_array($defaultProfile->params) ? $defaultProfile->params : [],
        );

        return [
            'profile_id' => $defaultProfile->id,
            'scope' => $saved['scope'] ?? 'selected',
            'columns' => $saved['columns'] ?? [],
            'date_scope' => $saved['date_scope'] ?? 'all',
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function stripQuickSearchCriteria(array $params): array
    {
        unset($params['search'], $params['search2']);

        return $params;
    }

    /**
     * @return array<string, mixed>
     */
    private function searchSortOverridesFromForm(Request $request): array
    {
        return array_filter(
            $request->only([
                'search',
                'search2',
                'sort1_column',
                'sort1_dir',
                'sort2_column',
                'sort2_dir',
            ]),
            fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }

    /**
     * Date and field-filter criteria from the filter form (not column display / profile scope).
     *
     * @return array<string, mixed>
     */
    private function filterCriteriaOverridesFromForm(Request $request): array
    {
        return array_filter(
            $request->only([
                'date_scope',
                'date_column',
                'start_date',
                'end_date',
                ...ImeiFieldFilter::queryKeys(),
            ]),
            fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function filterParamsForRedirect(array $params): array
    {
        return array_filter(
            $this->sanitizeFilterProfileParams($params),
            fn (mixed $value): bool => $value !== null && $value !== '',
        );
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function sanitizeFilterProfileParams(array $params): array
    {
        unset($params['from_filter']);

        return $params;
    }

    /**
     * @param  array<string, mixed>  $redirectParams
     */
    private function filterSearchRedirectMatchesRequest(Request $request, array $redirectParams): bool
    {
        $current = $this->filterParamsForRedirect(
            $this->sanitizeFilterProfileParams($this->imeiFilterParams($request)),
        );

        return $current === $redirectParams;
    }

    /**
     * If a filter profile is active for this user, apply its params to the current request
     * (without overwriting explicitly-provided query values) and ensure profile_id is present.
     *
     * @param  \Illuminate\Support\Collection<int, ImeiFilter>|null  $savedFilters
     */
    /**
     * Quick search should keep the session profile and submitted filter state;
     * it must not re-load saved profile params (which can swap the active profile).
     */
    private function applySessionProfileForQuickSearch(Request $request): void
    {
        $user = $request->user();
        if (! $user) {
            return;
        }

        $sessionProfileId = (int) $request->session()->get(self::ACTIVE_PROFILE_SESSION_KEY, 0);
        if ($sessionProfileId === 0) {
            return;
        }

        $request->session()->put(self::ACTIVE_PROFILE_SESSION_KEY, $sessionProfileId);
        $request->merge(['profile_id' => $sessionProfileId]);
    }

    private function applyActiveFilterProfile(Request $request, $savedFilters = null): ?ImeiFilter
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $profileId = $request->input('profile_id') ?: $request->session()->get(self::ACTIVE_PROFILE_SESSION_KEY);
        if (! $profileId) {
            return null;
        }

        $profileId = (int) $profileId;
        $request->session()->put(self::ACTIVE_PROFILE_SESSION_KEY, $profileId);

        /** @var ImeiFilter|null $filter */
        $filter = $savedFilters instanceof \Illuminate\Support\Collection
            ? $savedFilters->firstWhere('id', $profileId)
            : ImeiFilter::query()->where('user_id', $user->id)->whereKey($profileId)->first();

        if (! $filter) {
            return null;
        }

        $params = is_array($filter->params) ? $filter->params : [];

        foreach ($params as $key => $value) {
            if (! $request->has($key)) {
                $request->merge([$key => $value]);
            }
        }

        if (! $request->has('profile_id')) {
            $request->merge(['profile_id' => $profileId]);
        }

        return $filter;
    }

    private function activeProfileName(Request $request): ?string
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $profileId = $request->input('profile_id') ?: $request->session()->get(self::ACTIVE_PROFILE_SESSION_KEY);
        if (! $profileId) {
            return null;
        }

        return ImeiFilter::query()
            ->where('user_id', $user->id)
            ->whereKey((int) $profileId)
            ->value('name');
    }

    /**
     * @return Collection<int, ImeiType>
     */
    private function imeiTypesForForm(): Collection
    {
        return ImeiType::query()->orderByDesc('type')->get();
    }

    /**
     * @return Collection<int, ImeiStatus>
     */
    private function imeiStatusesForForm(): Collection
    {
        return ImeiStatus::query()->orderBy('status')->get();
    }

    /**
     * @return Collection<int, ImeiSaleType>
     */
    private function imeiSaleTypesForForm(): Collection
    {
        return ImeiSaleType::query()->orderBy('sale_type')->get();
    }

    /**
     * @return Collection<int, ImeiLocation>
     */
    private function imeiLocationsForForm(): Collection
    {
        return ImeiLocation::query()->orderBy('location')->get();
    }

    /**
     * @return Collection<int, ImeiMake>
     */
    private function imeiMakesForForm(): Collection
    {
        return ImeiMake::query()->orderBy('make')->get();
    }

    /**
     * @return Collection<int, ImeiModel>
     */
    private function allImeiModelsForForm(): Collection
    {
        return ImeiModel::query()->orderBy('make')->orderBy('model')->orderBy('serial')->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function imeiRecordForLookup(Imei $imei): array
    {
        return [
            'id' => $imei->id,
            'imei' => $imei->imei,
            'date_in' => $imei->date_in?->format('Y-m-d\TH:i'),
            'make' => $imei->make,
            'model' => $imei->model,
            'sn' => $imei->sn,
            'location' => $imei->location,
            'cash_stock_type' => $imei->cash_stock_type,
            'type' => $imei->type,
            'status' => $imei->status,
            'notes' => $imei->notes,
            'phonenumber' => $imei->phonenumber,
            'ref' => $imei->ref,
            'staff' => $imei->staff,
            'item_code' => $imei->item_code,
            'ourON' => $imei->ourON,
            'salesON' => $imei->salesON,
            'cost_excl' => $imei->cost_excl,
            'selling_price' => $imei->selling_price,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $viewRecord
     */
    private function imeiFormView(
        ?array $viewRecord,
        ?string $createPageHeading,
        ?string $createPageIntro,
        ?string $defaultImeiNonStandard,
        ?string $returnQuery,
        bool $embedded = false,
        bool $embeddedClose = false,
        bool $editableOnLoad = false,
    ): View {
        return view('imeis.create', [
            'columnLabels' => self::COLUMNS,
            'vatPercent' => \App\Support\ImeiCostIncl::vatPercent(),
            'customerDetailsMaxLength' => ImeiTextLimits::CUSTOMER_DETAILS_MAX,
            'dealDetailsMaxLength' => ImeiTextLimits::DEAL_DETAILS_MAX,
            'viewRecord' => $viewRecord,
            'editableOnLoad' => $editableOnLoad,
            'createPageHeading' => $createPageHeading,
            'createPageIntro' => $createPageIntro,
            'defaultImeiNonStandard' => $defaultImeiNonStandard,
            'embedded' => $embedded,
            'embeddedClose' => $embeddedClose,
            'layout' => $embedded ? 'layouts.embedded' : 'layouts.app',
            'returnListUrl' => $embedded ? 'embedded:close' : $this->indexUrlFromReturnQuery($returnQuery),
            'returnQuery' => $returnQuery,
            'imeiTypes' => $this->imeiTypesForForm(),
            'imeiStatuses' => $this->imeiStatusesForForm(),
            'imeiSaleTypes' => $this->imeiSaleTypesForForm(),
            'canSelectDeletedStatus' => ImeiDeletedStatus::userCanViewDeleted(auth()->user()),
            'imeiLocations' => $this->imeiLocationsForForm(),
            'imeiMakes' => $this->imeiMakesForForm(),
            'allImeiModels' => $this->allImeiModelsForForm(),
        ]);
    }

    private function redirectAfterImeiUpdate(Imei $imei, ?string $returnQuery, string $message, bool $embedded = false): RedirectResponse
    {
        if ($embedded) {
            return redirect()->to(route('imeis.edit', $imei).'?embedded=1&close=1')
                ->with('message', $message);
        }

        $returnListUrl = $this->indexUrlFromReturnQuery($returnQuery);
        if ($returnListUrl !== null) {
            return redirect()->to($returnListUrl)->with('message', $message);
        }

        return redirect()->route('imeis.index', [
            'search' => $imei->imei,
            'scope' => 'all',
            'date_scope' => 'all',
        ])->with('message', $message);
    }

    private function returnQueryStringFromRequest(Request $request): ?string
    {
        $returnQuery = $request->input('return_query', $request->query('return_query'));
        if (! is_string($returnQuery)) {
            return null;
        }

        $returnQuery = trim($returnQuery);
        if ($returnQuery === '') {
            return null;
        }

        if ($this->indexUrlFromReturnQuery($returnQuery) === null) {
            return null;
        }

        return $returnQuery;
    }

    private function indexUrlFromReturnQuery(?string $returnQuery): ?string
    {
        if ($returnQuery === null || trim($returnQuery) === '') {
            return null;
        }

        parse_str($returnQuery, $params);
        if (! is_array($params)) {
            return null;
        }

        if (($params['return_to'] ?? '') === CashDevicesTable::RETURN_TO) {
            return route('dashboard', array_filter([
                'sort' => CashDevicesTable::sortColumn(is_string($params['sort'] ?? null) ? $params['sort'] : null),
                'dir' => CashDevicesTable::sortDir(is_string($params['dir'] ?? null) ? $params['dir'] : null),
                'sale_type' => is_string($params['sale_type'] ?? null) ? $params['sale_type'] : null,
                'imei_type_id' => is_numeric($params['imei_type_id'] ?? null) ? (int) $params['imei_type_id'] : null,
            ], fn (mixed $value): bool => $value !== null && $value !== ''));
        }

        $filtered = array_intersect_key($params, array_flip(self::INDEX_RETURN_QUERY_KEYS));
        if ($filtered === []) {
            return route('imeis.index');
        }

        return route('imeis.index', $filtered);
    }

    private function abortIfDeletedAndNotAuthorized(Request $request, Imei $imei): void
    {
        abort_if(
            ImeiDeletedStatus::isDeleted($imei) && ! ImeiDeletedStatus::userCanViewDeleted($request->user()),
            404,
        );
    }
}
