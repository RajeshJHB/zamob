<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureRoleManager;
use App\Http\Requests\BulkUpdateUserRolesRequest;
use App\Http\Requests\UpdateUserRolesRequest;
use App\Models\Role;
use App\Models\User;
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

        $requestedRoleIds = $request->input('roles', []);

        if (! $this->roleManagerStillExistsAfterSingleUpdate($user, $requestedRoleIds)) {
            return redirect()
                ->route('user-roles.edit', $user)
                ->with('error', 'You must keep at least one Role Manager.');
        }

        $user->syncRoles($requestedRoleIds);

        return redirect()
            ->route('user-roles.index')
            ->with('success', 'Roles updated successfully for '.$user->name.'.');
    }

    public function bulkUpdate(BulkUpdateUserRolesRequest $request): RedirectResponse
    {
        $entries = $request->input('user_roles', []);

        if (! $this->roleManagerStillExistsAfterBulkUpdate($entries)) {
            return redirect()
                ->route('user-roles.index')
                ->with('error', 'You must keep at least one Role Manager.');
        }

        foreach ($entries as $entry) {
            $user = User::query()->findOrFail((int) $entry['user_id']);

            abort_unless($user->email_verified_at !== null, 404);

            $user->syncRoles($entry['roles'] ?? []);
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

    /**
     * @param  list<int|string>  $requestedRoleIds
     */
    private function roleManagerStillExistsAfterSingleUpdate(User $target, array $requestedRoleIds): bool
    {
        $roleManagerId = $this->roleManagerId();

        if ($roleManagerId === null) {
            return true;
        }

        $roleManagerId = (int) $roleManagerId;
        $requested = collect($requestedRoleIds)->map(fn (int|string $id): int => (int) $id);

        $targetCurrentlyManager = $target->roles()->whereKey($roleManagerId)->exists();
        $targetWillBeManager = $requested->contains($roleManagerId);

        if (! $targetCurrentlyManager) {
            return true;
        }

        if ($targetWillBeManager) {
            return true;
        }

        $otherManagersCount = User::query()
            ->whereKeyNot($target->getKey())
            ->whereHas('roles', fn ($query) => $query->whereKey($roleManagerId))
            ->count();

        return $otherManagersCount > 0;
    }

    /**
     * @param  array<int, array{user_id: mixed, roles?: mixed}>  $entries
     */
    private function roleManagerStillExistsAfterBulkUpdate(array $entries): bool
    {
        $roleManagerId = $this->roleManagerId();

        if ($roleManagerId === null) {
            return true;
        }

        $roleManagerId = (int) $roleManagerId;

        $managerIds = User::query()
            ->whereHas('roles', fn ($query) => $query->whereKey($roleManagerId))
            ->pluck('id')
            ->map(fn (int $id): int => $id)
            ->all();

        $resultingManagers = collect($managerIds)->unique()->values();

        foreach ($entries as $entry) {
            $userId = (int) ($entry['user_id'] ?? 0);
            $roleIds = collect($entry['roles'] ?? [])->map(fn (mixed $id): int => (int) $id);

            if ($userId <= 0) {
                continue;
            }

            if ($roleIds->contains($roleManagerId)) {
                if (! $resultingManagers->contains($userId)) {
                    $resultingManagers->push($userId);
                }
            } else {
                $resultingManagers = $resultingManagers->reject(fn (int $id): bool => $id === $userId)->values();
            }
        }

        return $resultingManagers->isNotEmpty();
    }

    private function roleManagerId(): ?int
    {
        $id = Role::query()->where('number', 1)->value('id');

        return $id === null ? null : (int) $id;
    }
}
