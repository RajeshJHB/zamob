<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\ImeiController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceNoteController;
use App\Http\Controllers\Settings\DefaultSettingsController;
use App\Http\Controllers\Settings\ImeiLocationController;
use App\Http\Controllers\Settings\ImeiMakeController;
use App\Http\Controllers\Settings\ImeiModelController;
use App\Http\Controllers\Settings\ImeiSaleTypeController;
use App\Http\Controllers\Settings\ImeiStatusController;
use App\Http\Controllers\Settings\ImeiTypeController;
use App\Http\Controllers\Settings\NoteSettingsController;
use App\Http\Controllers\Settings\NoteTypeController;
use App\Http\Controllers\Settings\VatSettingsController;
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

Route::get('/email/verify/{id}/{token}', [EmailVerificationController::class, 'verify'])
    ->middleware(['throttle:6,1'])
    ->name('verification.verify');

// Email Verification Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])->name('verification.send');
});

// Authenticated Routes
Route::middleware(['auth', 'verified', 'role.assigned'])->group(function () {
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
    Route::get('/settings/sale-types', [ImeiSaleTypeController::class, 'index'])->name('settings.sale-types.index');
    Route::post('/settings/sale-types', [ImeiSaleTypeController::class, 'store'])->name('settings.sale-types.store');
    Route::put('/settings/sale-types/{imeiSaleType}', [ImeiSaleTypeController::class, 'update'])->name('settings.sale-types.update');
    Route::delete('/settings/sale-types/{imeiSaleType}', [ImeiSaleTypeController::class, 'destroy'])->name('settings.sale-types.destroy');
    Route::get('/settings/notes', [NoteSettingsController::class, 'index'])->name('settings.notes.index');
    Route::get('/settings/note-types', [NoteTypeController::class, 'index'])->name('settings.note-types.index');
    Route::post('/settings/note-types', [NoteTypeController::class, 'store'])->name('settings.note-types.store');
    Route::put('/settings/note-types/{noteType}', [NoteTypeController::class, 'update'])->name('settings.note-types.update');
    Route::delete('/settings/note-types/{noteType}', [NoteTypeController::class, 'destroy'])->name('settings.note-types.destroy');
    Route::get('/settings/vat', [VatSettingsController::class, 'index'])->name('settings.vat.index');
    Route::put('/settings/vat', [VatSettingsController::class, 'update'])->name('settings.vat.update');
    Route::get('/settings/default', [DefaultSettingsController::class, 'index'])->name('settings.default.index');
    Route::put('/settings/default', [DefaultSettingsController::class, 'update'])->name('settings.default.update');
    Route::get('/contacts', [ContactController::class, 'search'])->name('contacts.index');
    Route::get('/contacts/search', [ContactController::class, 'legacyContactsSearchRedirect'])->name('contacts.search');
    Route::get('/contacts/notes/search', [NotesController::class, 'legacyContactsNotesSearchRedirect'])->name('contacts.notes.search');
    Route::get('/notes', [NotesController::class, 'search'])->name('notes.index');
    Route::get('/notes/print', [NotesController::class, 'print'])->name('notes.print');
    Route::get('/notes/search', [NotesController::class, 'legacyNotesSearchRedirect'])->name('notes.search');
    Route::get('/contacts/create', [ContactController::class, 'create'])->name('contacts.create');
    Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
    Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
    Route::get('/contacts/{contact}/edit', [ContactController::class, 'edit'])->name('contacts.edit');
    Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
    Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
    Route::get('/contacts/{contact}/service-notes/create', [ServiceNoteController::class, 'create'])->name('contacts.service-notes.create');
    Route::post('/contacts/{contact}/service-notes', [ServiceNoteController::class, 'store'])->name('contacts.service-notes.store');
    Route::get('/service-notes/{serviceNote}/edit', [ServiceNoteController::class, 'edit'])->name('service-notes.edit');
    Route::put('/service-notes/{serviceNote}', [ServiceNoteController::class, 'update'])->name('service-notes.update');
    Route::delete('/service-notes/{serviceNote}', [ServiceNoteController::class, 'destroy'])->name('service-notes.destroy');
    Route::get('/service-notes/{serviceNote}/attachment', [ServiceNoteController::class, 'attachment'])->name('service-notes.attachment');
    Route::get('/service-notes/{serviceNote}/print', [ServiceNoteController::class, 'print'])->name('service-notes.print');
    Route::get('/imeis/create', [ImeiController::class, 'create'])->name('imeis.create');
    Route::get('/imeis/lookup', [ImeiController::class, 'lookup'])->name('imeis.lookup');
    Route::get('/imeis/last-for-copy', [ImeiController::class, 'lastForCopy'])->name('imeis.last-for-copy');
    Route::get('/imeis/contacts/browse', [ImeiController::class, 'browseContacts'])->name('imeis.contacts.browse');
    Route::get('/imeis/contacts/{contact}/service-notes/browse', [ImeiController::class, 'browseContactServiceNotes'])->name('imeis.contacts.service-notes.browse');
    Route::post('/imeis', [ImeiController::class, 'store'])->name('imeis.store');
    Route::put('/imeis/{imei}', [ImeiController::class, 'update'])->name('imeis.update');
    Route::delete('/imeis/{imei}', [ImeiController::class, 'destroy'])->name('imeis.destroy');
    Route::get('/imeis/filter', [ImeiController::class, 'filter'])->name('imeis.filter');
    Route::post('/imeis/filter/save', [ImeiController::class, 'saveFilter'])->name('imeis.filter.save');
    Route::get('/imeis/filter/apply/{filter}', [ImeiController::class, 'applyFilter'])->name('imeis.filter.apply');
    Route::delete('/imeis/filter/{filter}', [ImeiController::class, 'deleteFilter'])->name('imeis.filter.delete');
    Route::get('/imeis/filter/clear', [ImeiController::class, 'clearFilterProfile'])->name('imeis.filter.clear');
    Route::post('/imeis/filter/default', [ImeiController::class, 'updateDefaultFilterProfile'])->name('imeis.filter.default');
    Route::get('/imeis/profile/apply/{filter}', [ImeiController::class, 'applyProfileFromIndex'])->name('imeis.profile.apply');
    Route::get('/imeis/profile/clear', [ImeiController::class, 'clearProfileFromIndex'])->name('imeis.profile.clear');
    Route::get('/imeis/search/reset', [ImeiController::class, 'resetSearch'])->name('imeis.search.reset');
    Route::get('/imeis/receipt/logo', [ImeiController::class, 'receiptLogo'])->name('imeis.receipt.logo');
    Route::get('/imeis/{imei}/receipt', [ImeiController::class, 'receipt'])->name('imeis.receipt');
    Route::get('/imeis/{imei}/edit', [ImeiController::class, 'edit'])->name('imeis.edit');
    Route::get('/imeis/print', [ImeiController::class, 'print'])->name('imeis.print');
    Route::post('/imeis/bulk-status', [ImeiController::class, 'bulkChangeStatus'])->name('imeis.bulk-status');
    Route::get('/imeis', [ImeiController::class, 'index'])->name('imeis.index');
});

// Profile Routes (accessible even when unverified or without roles)
Route::middleware(['auth'])->group(function () {
    Route::get('/help', HelpController::class)->name('help.index');
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
    Route::get('/{user}/edit', [UserRoleController::class, 'edit'])->name('edit');
    Route::put('/{user}', [UserRoleController::class, 'update'])->name('update');
    Route::delete('/{user}', [UserRoleController::class, 'destroy'])->name('destroy');
});
