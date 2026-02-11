<?php

namespace App\Http\Controllers;

use App\Models\Imei;
use App\Models\ImeiFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImeiController extends Controller
{
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
        'notes' => 'Notes',
        'phonenumber' => 'Phone',
        'ref' => 'Ref',
        'staff' => 'Staff',
        'item_code' => 'Item code',
        'stock_take_date' => 'Stock take date',
        'ourON' => 'ourON',
        'salesON' => 'salesON',
        'cost_excl' => 'Cost excl',
        'selling_price' => 'Selling price',
    ];

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
}
