<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SettingsController extends Controller
{
    public const SECTION_LABELS = [
        'makes' => 'Make',
        'models' => 'Models',
        'locations' => 'Locations',
        'types' => 'Types',
        'status' => 'Status',
    ];

    public function index(): View
    {
        return view('settings.index', [
            'sections' => self::SECTION_LABELS,
        ]);
    }

    public function section(string $section): View
    {
        $label = self::SECTION_LABELS[$section] ?? null;
        abort_if($label === null, 404);

        return view('settings.section', [
            'section' => $section,
            'sectionLabel' => $label,
        ]);
    }
}
