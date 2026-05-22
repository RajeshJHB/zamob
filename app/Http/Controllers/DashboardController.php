<?php

namespace App\Http\Controllers;

use App\Models\Imei;
use App\Support\CashDevicesTable;
use App\Support\ImeiCashDeviceType;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $sort = CashDevicesTable::sortColumn($request->input('sort'));
        $dir = CashDevicesTable::sortDir($request->input('dir'));

        $cashDevices = Imei::query()
            ->where('type', ImeiCashDeviceType::TYPE)
            ->where('status', ImeiCashDeviceType::STATUS)
            ->tap(fn ($query) => CashDevicesTable::applySort($query, $sort, $dir))
            ->get();

        $cashDevicesReturnQuery = CashDevicesTable::returnQuery($sort, $dir);

        return view('dashboard', [
            'cashDevices' => $cashDevices,
            'cashDevicesSort' => $sort,
            'cashDevicesSortDir' => $dir,
            'cashDevicesReturnQuery' => $cashDevicesReturnQuery,
        ]);
    }
}
