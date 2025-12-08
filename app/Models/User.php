<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role');
    }

    public function hasRole(int $roleNumber): bool
    {
        return $this->roles()->where('number', $roleNumber)->exists();
    }

    public function isRoleManager(): bool
    {
        return $this->hasRole(1);
    }

    public function isFirstUser(): bool
    {
        return $this->id === User::min('id');
    }

    /**
     * Ensure at least one role manager exists.
     * If no role managers exist, assign Role_1 to the oldest user.
     * If only one user exists, they automatically become a role manager.
     */
    public static function ensureRoleManagerExists(): void
    {
        $roleManager = Role::where('number', 1)->first();
        
        if (! $roleManager) {
            return; // Role_1 doesn't exist yet, skip
        }

        $verifiedUsers = User::whereNotNull('email_verified_at')->get();
        
        // If only one user exists, make them a role manager
        if ($verifiedUsers->count() === 1) {
            $user = $verifiedUsers->first();
            if (! $user->isRoleManager()) {
                $user->roles()->syncWithoutDetaching([$roleManager->id]);
            }
            return;
        }

        // Check if any role managers exist
        $roleManagers = User::whereHas('roles', function ($query) use ($roleManager) {
            $query->where('roles.id', $roleManager->id);
        })->whereNotNull('email_verified_at')->get();

        // If no role managers exist, assign to oldest user
        if ($roleManagers->isEmpty() && $verifiedUsers->isNotEmpty()) {
            $oldestUser = $verifiedUsers->sortBy('id')->first();
            if ($oldestUser) {
                $oldestUser->roles()->syncWithoutDetaching([$roleManager->id]);
            }
        }
    }
}
