<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class NoteSettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.notes.index');
    }
}
