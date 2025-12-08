<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureRoleManager;
use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            'verified',
            EnsureRoleManager::class,
        ];
    }

    public function index(): View
    {
        $roles = Role::orderBy('number')->get();

        return view('roles.index', compact('roles'));
    }

    public function store(RoleStoreRequest $request): RedirectResponse
    {
        $nextNumber = Role::max('number') ? Role::max('number') + 1 : 2;

        Role::create([
            'number' => $nextNumber,
            'name' => $request->name,
        ]);

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function update(RoleUpdateRequest $request, Role $role): RedirectResponse
    {
        $role->update([
            'name' => $request->name,
        ]);

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $lastRole = Role::orderBy('number', 'desc')->first();

        if ($lastRole && $lastRole->id === $role->id) {
            // Only allow deleting the last role
            if ($role->number === 1) {
                return redirect()->route('roles.index')->with('error', 'Cannot delete Role Manager (Role_1).');
            }

            $role->users()->detach();
            $role->delete();

            return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
        }

        return redirect()->route('roles.index')->with('error', 'You can only delete the last role.');
    }
}
