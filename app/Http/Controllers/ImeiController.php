<?php

namespace App\Http\Controllers;

use App\Models\Imei;
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
                ->route('imeis.filter', $request->only(['scope', 'date_scope', 'date_column', 'start_date', 'end_date', 'columns', 'search']))
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
            'filterParams' => $request->only(['scope', 'columns', 'date_scope', 'date_column', 'start_date', 'end_date', 'search']),
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

        $query = Imei::query()->orderByDesc('date_in');

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $term = '%' . $search . '%';
            $query->where(function ($q) use ($term) {
                foreach (array_keys(self::COLUMNS) as $column) {
                    $q->orWhere($column, 'LIKE', $term);
                }
            });
        }

        if ($dateScope === 'range' && $dateColumn && in_array($dateColumn, array_keys(self::DATE_COLUMNS), true) && $startDate && $endDate) {
            $query->whereBetween($dateColumn, [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59',
            ]);
        }

        return $query;
    }
}
