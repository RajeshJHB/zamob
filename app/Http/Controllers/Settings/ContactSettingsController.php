<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ContactSettingsController extends Controller
{
    public function index(): View
    {
        return view('settings.contacts.index');
    }
}
