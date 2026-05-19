<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImeiRequest;
use App\Http\Requests\UpdateImeiRequest;
use App\Models\Imei;
use App\Models\ImeiFilter;
use App\Models\ImeiLocation;
use App\Models\ImeiMake;
use App\Models\ImeiModel;
use App\Models\ImeiStatus;
use App\Models\ImeiType;
use App\Support\ImeiStaffAudit;
use App\Support\ImeiValidator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImeiController extends Controller
{
    /**
     * Query keys allowed when returning from view/edit to the IMEI results table.
     *
     * @var list<string>
     */
    private const INDEX_RETURN_QUERY_KEYS = [
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
        'page',
    ];

    public const DATE_COLUMNS = [
        'date_in' => 'Date In',
        'date_updated' => 'Date Updated',
    ];

    public const COLUMNS = [
        'id' => 'ID',
        'date_in' => 'Date in',
        'date_updated' => 'Date updated',
        'make' => 'Make',
        'model' => 'Model',
        'imei' => 'IMEI',
        'sn' => 'SN',
        'location' => 'Location',
        'type' => 'Type',
        'status' => 'Status',
        'notes' => 'Customer Details',
        'phonenumber' => 'Phone Number',
        'ref' => 'Deal Details',
        'staff' => 'Staff',
        'item_code' => 'Item code',
        'ourON' => 'ourON',
        'salesON' => 'salesON',
        'cost_excl' => 'Cost excl',
        'selling_price' => 'Selling price',
    ];

    public function create(): View
    {
        $viewRecord = null;
        if ($id = session()->pull('imei_view_id')) {
            $imei = Imei::query()->find($id);
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
        );
    }

    public function edit(Request $request, Imei $imei): View
    {
        $digits = ImeiValidator::normalizeDigits($imei->imei);
        $returnQuery = $this->returnQueryStringFromRequest($request);

        return $this->imeiFormView(
            viewRecord: $this->imeiRecordForLookup($imei),
            createPageHeading: 'View IMEI',
            createPageIntro: 'This record is loaded from Find IMEI\'s. Details are read-only until you choose Edit, then Save to update.',
            defaultImeiNonStandard: ImeiValidator::isValidChecksum($digits) ? '0' : '1',
            returnQuery: $returnQuery,
        );
    }

    public function receipt(Imei $imei): View
    {
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

            $record = Imei::query()->whereNormalizedImei($normalizedNs)->first();

            return response()->json([
                'valid' => true,
                'exists' => $record !== null,
                'canonical_imei' => $normalizedNs,
                'record' => $record ? $this->imeiRecordForLookup($record) : null,
                'non_standard' => true,
            ]);
        }

        if (! ImeiValidator::isValidChecksum($normalized)) {
            return response()->json([
                'valid' => false,
                'exists' => false,
                'canonical_imei' => strlen($normalized) === 15 ? $normalized : null,
                'record' => null,
                'non_standard' => false,
                'message' => 'Enter a valid 15-digit IMEI (check digit must be correct).',
            ]);
        }

        $record = Imei::query()->whereNormalizedImei($normalized)->first();

        return response()->json([
            'valid' => true,
            'exists' => $record !== null,
            'canonical_imei' => $normalized,
            'record' => $record ? $this->imeiRecordForLookup($record) : null,
            'non_standard' => false,
        ]);
    }

    public function store(StoreImeiRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (empty($data['date_in'])) {
            $data['date_in'] = now();
        }
        $data['date_updated'] = now();
        $data['staff'] = ImeiStaffAudit::appendEmail('', (string) $request->user()->email);
        $imei = Imei::create($data);

        return $this->redirectToImeiView($imei, $this->returnQueryStringFromRequest($request), 'IMEI record created.');
    }

    public function update(UpdateImeiRequest $request, Imei $imei): RedirectResponse
    {
        $data = $request->validated();
        $data['date_updated'] = now();
        $data['staff'] = ImeiStaffAudit::appendEmail((string) $imei->staff, (string) $request->user()->email);
        $imei->update($data);

        return $this->redirectToImeiView($imei, $this->returnQueryStringFromRequest($request), 'IMEI record updated.');
    }

    public function destroy(Request $request, Imei $imei): RedirectResponse
    {
        abort_unless($request->user()->canDeleteImeiReferenceData(), 403);

        $returnQuery = $this->returnQueryStringFromRequest($request);
        $imei->delete();

        $returnListUrl = $this->indexUrlFromReturnQuery($returnQuery);
        if ($returnListUrl !== null) {
            return redirect()->to($returnListUrl)->with('message', 'IMEI record deleted.');
        }

        return redirect()
            ->route('imeis.create')
            ->with('message', 'IMEI record deleted.');
    }

    public function filter(Request $request): View
    {
        $user = $request->user();

        $savedFilters = ImeiFilter::query()
            ->when($user, fn ($q) => $q->where('user_id', $user->id))
            ->orderBy('name')
            ->get();

        $currentProfileName = null;
        $currentProfileId = $request->input('profile_id');
        if ($currentProfileId) {
            $current = $savedFilters->firstWhere('id', (int) $currentProfileId);
            if ($current) {
                $currentProfileName = $current->name;
            }
        }

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
            'savedFilters' => $savedFilters,
            'currentProfileName' => $currentProfileName,
        ]);
    }

    public function index(Request $request): View|RedirectResponse
    {
        $dateScope = $request->input('date_scope', 'all');
        $dateColumn = $request->input('date_column');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($dateScope === 'range' && $startDate && $endDate && $startDate > $endDate) {
            return redirect()
                ->route('imeis.filter', $request->only([
                    'scope',
                    'date_scope',
                    'date_column',
                    'start_date',
                    'end_date',
                    'columns',
                    'search',
                    'search2',
                    'sort1_column',
                    'sort1_dir',
                    'sort2_column',
                    'sort2_dir',
                ]))
                ->with('error', 'The dates must be fixed. Start date cannot be after end date.');
        }

        $query = $this->buildImeiQuery($request);

        $imeis = $query->paginate(25)->withQueryString();

        $selectedColumns = $request->input('columns');
        if (is_string($selectedColumns)) {
            $selectedColumns = array_filter(explode(',', $selectedColumns));
        }
        if (empty($selectedColumns) || $request->input('scope') === 'all') {
            $selectedColumns = array_keys(self::COLUMNS);
        } else {
            $selectedColumns = array_intersect($selectedColumns, array_keys(self::COLUMNS));
            if (empty($selectedColumns)) {
                $selectedColumns = array_keys(self::COLUMNS);
            }
        }

        return view('imeis.index', [
            'imeis' => $imeis,
            'columns' => $selectedColumns,
            'columnLabels' => self::COLUMNS,
            'filterParams' => $request->only([
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
            ]),
        ]);
    }

    public function print(Request $request): View
    {
        $query = $this->buildImeiQuery($request);
        $imeis = $query->get();

        $selectedColumns = $request->input('columns');
        if (is_string($selectedColumns)) {
            $selectedColumns = array_filter(explode(',', $selectedColumns));
        }
        if (empty($selectedColumns) || $request->input('scope') === 'all') {
            $selectedColumns = array_keys(self::COLUMNS);
        } else {
            $selectedColumns = array_intersect($selectedColumns, array_keys(self::COLUMNS));
            if (empty($selectedColumns)) {
                $selectedColumns = array_keys(self::COLUMNS);
            }
        }

        return view('imeis.print', [
            'imeis' => $imeis,
            'columns' => $selectedColumns,
            'columnLabels' => self::COLUMNS,
        ]);
    }

    private function buildImeiQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $dateScope = $request->input('date_scope', 'all');
        $dateColumn = $request->input('date_column');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Imei::query();

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                foreach (array_keys(self::COLUMNS) as $column) {
                    $q->orWhere($column, 'LIKE', $term);
                }
            });
        }

        $search2 = trim((string) $request->input('search2', ''));
        if ($search2 !== '') {
            $term2 = '%'.$search2.'%';
            $query->where(function ($q) use ($term2) {
                foreach (array_keys(self::COLUMNS) as $column) {
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

        // Sorting: up to two levels based on selected columns.
        $allowedColumns = array_keys(self::COLUMNS);

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

        // Default sort if nothing chosen: newest Date In first.
        if (! $hasSort) {
            $query->orderByDesc('date_in');
        }

        return $query;
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

        return redirect()
            ->route('imeis.filter', $request->query())
            ->with('message', 'Filter profile deleted.');
    }

    /**
     * @return Collection<int, ImeiType>
     */
    private function imeiTypesForForm(): Collection
    {
        return ImeiType::query()->orderBy('type')->get();
    }

    /**
     * @return Collection<int, ImeiStatus>
     */
    private function imeiStatusesForForm(): Collection
    {
        return ImeiStatus::query()->orderBy('status')->get();
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
    ): View {
        return view('imeis.create', [
            'columnLabels' => self::COLUMNS,
            'viewRecord' => $viewRecord,
            'createPageHeading' => $createPageHeading,
            'createPageIntro' => $createPageIntro,
            'defaultImeiNonStandard' => $defaultImeiNonStandard,
            'returnListUrl' => $this->indexUrlFromReturnQuery($returnQuery),
            'returnQuery' => $returnQuery,
            'imeiTypes' => $this->imeiTypesForForm(),
            'imeiStatuses' => $this->imeiStatusesForForm(),
            'imeiLocations' => $this->imeiLocationsForForm(),
            'imeiMakes' => $this->imeiMakesForForm(),
            'allImeiModels' => $this->allImeiModelsForForm(),
        ]);
    }

    private function redirectToImeiView(Imei $imei, ?string $returnQuery, string $message): RedirectResponse
    {
        $url = route('imeis.edit', $imei);
        if ($returnQuery !== null && $this->indexUrlFromReturnQuery($returnQuery) !== null) {
            $url .= '?return_query='.rawurlencode($returnQuery);
        }

        return redirect()->to($url)->with('message', $message);
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

        $filtered = array_intersect_key($params, array_flip(self::INDEX_RETURN_QUERY_KEYS));
        if ($filtered === []) {
            return route('imeis.index');
        }

        return route('imeis.index', $filtered);
    }
}
