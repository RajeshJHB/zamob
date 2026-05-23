<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureRoleManager;
use App\Http\Requests\BulkUpdateUserRolesRequest;
use App\Http\Requests\UpdateUserRolesRequest;
use App\Models\User;
use App\Support\Role4Protection;
use App\Support\UserRoleTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class UserRoleController extends Controller implements HasMiddleware
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
        return view('user-roles.index', UserRoleTable::indexData());
    }

    public function edit(User $user): View
    {
        abort_unless($user->email_verified_at !== null, 404);

        $user->load('roles');

        return view('user-roles.edit', [
            'user' => $user,
            'roles' => UserRoleTable::indexData()['roles'],
        ]);
    }

    public function update(UpdateUserRolesRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->email_verified_at !== null, 404);

        $roles = Role4Protection::ensureRoleFourPreserved(
            $user,
            $request->input('roles', []),
        );

        $user->syncRoles($roles);

        return redirect()
            ->route('user-roles.index')
            ->with('success', 'Roles updated successfully for '.$user->name.'.');
    }

    public function bulkUpdate(BulkUpdateUserRolesRequest $request): RedirectResponse
    {
        foreach ($request->input('user_roles', []) as $entry) {
            $user = User::query()->findOrFail((int) $entry['user_id']);

            abort_unless($user->email_verified_at !== null, 404);

            $roles = Role4Protection::ensureRoleFourPreserved(
                $user,
                $entry['roles'] ?? [],
            );

            $user->syncRoles($roles);
        }

        return redirect()
            ->route('user-roles.index')
            ->with('success', 'Roles updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('user-roles.index')->with('error', 'You cannot delete your own account.');
        }

        if ($user->isFirstUser()) {
            return redirect()->route('user-roles.index')->with('error', 'The first user cannot be deleted.');
        }

        $user->delete();

        User::ensureRoleManagerExists();

        return redirect()->route('user-roles.index')->with('success', 'User deleted successfully.');
    }
}
