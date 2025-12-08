<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureRoleManager;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $users = User::with('roles')
            ->whereNotNull('email_verified_at')
            ->orderBy('name')
            ->get();
        $roles = Role::orderBy('number')->get();

        return view('user-roles.index', compact('users', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        // Prevent logged-in role manager from removing their own Role Manager role
        $roleManager = Role::where('number', 1)->first();
        $isCurrentUser = $user->id === auth()->id();
        $isCurrentUserRoleManager = $isCurrentUser && $user->isRoleManager();

        if ($isCurrentUserRoleManager && $roleManager) {
            $requestedRoles = $request->roles ?? [];
            // Ensure Role_1 is always included for the current user if they are a role manager
            if (! in_array($roleManager->id, $requestedRoles)) {
                $requestedRoles[] = $roleManager->id;
            }
            $user->roles()->sync($requestedRoles);
        } else {
            $user->roles()->sync($request->roles ?? []);
        }

        // Ensure at least one role manager exists after update
        User::ensureRoleManagerExists();

        return redirect()->route('user-roles.index')->with('success', 'User roles updated successfully.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'users' => ['required', 'array'],
            'users.*' => ['array'],
            'users.*.roles' => ['nullable', 'array'],
            'users.*.roles.*' => ['exists:roles,id'],
        ]);

        $roleManager = Role::where('number', 1)->first();
        $currentUserId = auth()->id();
        $updatedCount = 0;

        foreach ($request->users as $userId => $userData) {
            $user = User::find($userId);

            if (! $user) {
                continue;
            }

            // Get roles from request - if no checkboxes are checked, roles will be empty array
            $requestedRoles = $userData['roles'] ?? [];

            // Convert array keys to values if needed (checkbox arrays can come in different formats)
            if (! empty($requestedRoles) && array_keys($requestedRoles) !== range(0, count($requestedRoles) - 1)) {
                $requestedRoles = array_values($requestedRoles);
            }

            $isCurrentUser = (int) $userId === $currentUserId;
            $isCurrentUserRoleManager = $isCurrentUser && $user->isRoleManager();

            // Prevent logged-in role manager from removing their own Role Manager role
            if ($isCurrentUserRoleManager && $roleManager) {
                if (! in_array($roleManager->id, $requestedRoles)) {
                    $requestedRoles[] = $roleManager->id;
                }
            }

            $user->roles()->sync($requestedRoles);
            $updatedCount++;
        }

        // Ensure at least one role manager exists after update
        User::ensureRoleManagerExists();

        return redirect()->route('user-roles.index')->with('success', 'Roles Updated');
    }

    public function destroy(User $user): RedirectResponse
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('user-roles.index')->with('error', 'You cannot delete your own account.');
        }

        // Detach all roles before deleting
        $user->roles()->detach();

        // Delete the user
        $user->delete();

        // Ensure at least one role manager exists after deletion
        User::ensureRoleManagerExists();

        return redirect()->route('user-roles.index')->with('success', 'User deleted successfully.');
    }
}
