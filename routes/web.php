<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImeiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Settings\ImeiLocationController;
use App\Http\Controllers\Settings\ImeiMakeController;
use App\Http\Controllers\Settings\ImeiModelController;
use App\Http\Controllers\Settings\ImeiStatusController;
use App\Http\Controllers\Settings\ImeiTypeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserRoleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Password Reset Routes
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Email Verification Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
        $request->fulfill();

        // Ensure at least one role manager exists after email verification
        \App\Models\User::ensureRoleManagerExists();

        return redirect()->route('dashboard');
    })->middleware(['signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('message', 'Verification link sent!');
    })->middleware(['throttle:6,1'])->name('verification.send');
});

// Authenticated Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/makes', [ImeiMakeController::class, 'index'])->name('settings.makes.index');
    Route::post('/settings/makes', [ImeiMakeController::class, 'store'])->name('settings.makes.store');
    Route::put('/settings/makes/{imeiMake}', [ImeiMakeController::class, 'update'])->name('settings.makes.update');
    Route::delete('/settings/makes/{imeiMake}', [ImeiMakeController::class, 'destroy'])->name('settings.makes.destroy');
    Route::get('/settings/models', [ImeiModelController::class, 'index'])->name('settings.models.index');
    Route::post('/settings/models', [ImeiModelController::class, 'store'])->name('settings.models.store');
    Route::put('/settings/models/{imeiModel}', [ImeiModelController::class, 'update'])->name('settings.models.update');
    Route::delete('/settings/models/{imeiModel}', [ImeiModelController::class, 'destroy'])->name('settings.models.destroy');
    Route::get('/settings/locations', [ImeiLocationController::class, 'index'])->name('settings.locations.index');
    Route::post('/settings/locations', [ImeiLocationController::class, 'store'])->name('settings.locations.store');
    Route::put('/settings/locations/{imeiLocation}', [ImeiLocationController::class, 'update'])->name('settings.locations.update');
    Route::delete('/settings/locations/{imeiLocation}', [ImeiLocationController::class, 'destroy'])->name('settings.locations.destroy');
    Route::get('/settings/types', [ImeiTypeController::class, 'index'])->name('settings.types.index');
    Route::post('/settings/types', [ImeiTypeController::class, 'store'])->name('settings.types.store');
    Route::put('/settings/types/{imeiType}', [ImeiTypeController::class, 'update'])->name('settings.types.update');
    Route::delete('/settings/types/{imeiType}', [ImeiTypeController::class, 'destroy'])->name('settings.types.destroy');
    Route::get('/settings/status', [ImeiStatusController::class, 'index'])->name('settings.status.index');
    Route::post('/settings/status', [ImeiStatusController::class, 'store'])->name('settings.status.store');
    Route::put('/settings/status/{imeiStatus}', [ImeiStatusController::class, 'update'])->name('settings.status.update');
    Route::delete('/settings/status/{imeiStatus}', [ImeiStatusController::class, 'destroy'])->name('settings.status.destroy');
    Route::get('/imeis/create', [ImeiController::class, 'create'])->name('imeis.create');
    Route::get('/imeis/lookup', [ImeiController::class, 'lookup'])->name('imeis.lookup');
    Route::post('/imeis', [ImeiController::class, 'store'])->name('imeis.store');
    Route::put('/imeis/{imei}', [ImeiController::class, 'update'])->name('imeis.update');
    Route::delete('/imeis/{imei}', [ImeiController::class, 'destroy'])->name('imeis.destroy');
    Route::get('/imeis/filter', [ImeiController::class, 'filter'])->name('imeis.filter');
    Route::post('/imeis/filter/save', [ImeiController::class, 'saveFilter'])->name('imeis.filter.save');
    Route::get('/imeis/filter/apply/{filter}', [ImeiController::class, 'applyFilter'])->name('imeis.filter.apply');
    Route::delete('/imeis/filter/{filter}', [ImeiController::class, 'deleteFilter'])->name('imeis.filter.delete');
    Route::get('/imeis/receipt/logo', [ImeiController::class, 'receiptLogo'])->name('imeis.receipt.logo');
    Route::get('/imeis/{imei}/receipt', [ImeiController::class, 'receipt'])->name('imeis.receipt');
    Route::get('/imeis/{imei}/edit', [ImeiController::class, 'edit'])->name('imeis.edit');
    Route::get('/imeis/print', [ImeiController::class, 'print'])->name('imeis.print');
    Route::get('/imeis', [ImeiController::class, 'index'])->name('imeis.index');
});

// Profile Routes (accessible even when unverified after email change)
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [ProfileController::class, 'showPasswordResetForm'])->name('profile.password');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

// Role Management Routes (Role Manager only)
Route::middleware(['auth', 'verified'])->prefix('roles')->name('roles.')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::put('/{role}', [RoleController::class, 'update'])->name('update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
});

// User Role Assignment Routes (Role Manager only)
Route::middleware(['auth', 'verified'])->prefix('user-roles')->name('user-roles.')->group(function () {
    Route::get('/', [UserRoleController::class, 'index'])->name('index');
    Route::post('/bulk-update', [UserRoleController::class, 'bulkUpdate'])->name('bulk-update');
    Route::put('/{user}', [UserRoleController::class, 'update'])->name('update');
    Route::delete('/{user}', [UserRoleController::class, 'destroy'])->name('destroy');
});
