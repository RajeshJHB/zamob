<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public const SECTION_LABELS = [
        'makes' => 'Make',
        'models' => 'Models',
        'locations' => 'Locations',
        'types' => 'Types',
        'status' => 'Status',
        'sale_types' => 'Sale types',
    ];

    public function index(Request $request): View
    {
        $sections = self::SECTION_LABELS;

        if ($request->user()?->canDeleteImeiReferenceData() !== true) {
            unset($sections['sale_types']);
        }

        return view('settings.index', [
            'sections' => $sections,
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
