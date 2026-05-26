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
