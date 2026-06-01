<?php

namespace App\Http\Controllers;

use App\Models\Imei;
use App\Support\CashDevicesSaleTypeFilter;
use App\Support\CashDevicesTable;
use App\Support\CashDevicesTypeFilter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $sort = CashDevicesTable::sortColumn($request->input('sort'));
        $dir = CashDevicesTable::sortDir($request->input('dir'));
        $saleType = CashDevicesSaleTypeFilter::selected($request);
        $imeiTypeId = CashDevicesTypeFilter::selectedTypeId($request);
        $imeiType = CashDevicesTypeFilter::selected($request);

        $cashDevices = Imei::query()
            ->visibleTo($request->user())
            ->tap(fn ($query) => CashDevicesSaleTypeFilter::applySoldExclusion($query))
            ->tap(fn ($query) => CashDevicesSaleTypeFilter::applyToQuery($query, $saleType))
            ->tap(fn ($query) => CashDevicesTypeFilter::applyToQuery($query, $imeiType))
            ->tap(fn ($query) => CashDevicesTable::applySort($query, $sort, $dir))
            ->get();

        $cashDevicesReturnQuery = CashDevicesTable::returnQuery($sort, $dir, $saleType, $imeiTypeId);

        return view('dashboard', [
            'cashDevices' => $cashDevices,
            'cashDevicesSort' => $sort,
            'cashDevicesSortDir' => $dir,
            'cashDevicesReturnQuery' => $cashDevicesReturnQuery,
            'saleTypes' => CashDevicesSaleTypeFilter::selectableSaleTypes(),
            'imeiTypes' => CashDevicesTypeFilter::selectableTypes(),
            'selectedSaleType' => $saleType,
            'selectedImeiTypeId' => $imeiTypeId,
        ]);
    }
}
