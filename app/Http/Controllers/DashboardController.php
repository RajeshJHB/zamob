<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $user->load('roles');

        return view('dashboard', [
            'user' => $user,
            'roles' => $user->roles,
        ]);
    }
}
