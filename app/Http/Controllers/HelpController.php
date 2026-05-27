<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $version = (string) config('app.version', '1.0.0');

        $updateHistory = [
            '2026-05-27' => [
                'Version 1.2.0 — IMEI database column renamed from stock_take_date to cash_stock_type.',
                'IMEI browse: Print and Edit actions; Edit opens the record in edit mode.',
                'IMEI edit: unsaved-changes prompt when exiting with changes.',
                'IMEI form layout: Device section reorganised (IMEI, sale type, serial, item code, make/model, pricing, type/status).',
                'IMEI form: Make/Model row sizing; bold IMEI, make, and model values; deal phone number moved above deal details.',
                'Device receipt print: Model and IMEI text sizing and weight adjustments.',
                'Users without assigned roles: limited to profile menu only; app routes blocked until a role is assigned.',
            ],
            '2026-05-26' => [
                'Login screen navigation cleaned up (guest menu hidden).',
                'Dashboard home simplified.',
                'Navigation updated (center title / styling).',
                'Notes search: date filtering, type filtering, print view.',
                'IMEI filter: date picker constraints and validation.',
            ],
        ];

        return view('help.index', [
            'version' => $version,
            'showUpdateHistory' => $user?->canDeleteImeiReferenceData() === true,
            'updateHistory' => $updateHistory,
        ]);
    }
}
