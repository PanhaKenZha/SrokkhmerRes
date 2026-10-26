<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLES = ['admin' => 'Admin', 'owner' => 'Owner', 'staff' => 'Staff', 'customer' => 'Customer'];

    /** Roles that may sign in to the restaurant workspace. */
    public const WORKSPACE_ROLES = ['admin', 'owner', 'staff'];

    public const PERMISSIONS = [
        'orders.manage' => ['label' => 'View and update orders', 'roles' => ['admin', 'owner', 'staff']],
        'reports.view' => ['label' => 'See sales reports', 'roles' => ['admin', 'owner']],
        'menu.manage' => ['label' => 'Add and edit menu items', 'roles' => ['admin', 'owner']],
        'restaurants.manage' => ['label' => 'Manage restaurants & locations', 'roles' => ['admin', 'owner']],
        'banners.manage' => ['label' => 'Edit home page banners', 'roles' => ['admin', 'owner']],
        'users.manage' => ['label' => 'Add team members and change roles', 'roles' => ['admin', 'owner']],
        'roles.elevate' => ['label' => 'Make owners and admins', 'roles' => ['admin']],
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

    protected static function booted(): void
    {
        // The configured STAFF_EMAIL account is always created as an admin; everyone else starts as a customer.
        static::creating(function (User $user) {
            $user->role ??= $user->email === config('restaurant.staff_email') ? 'admin' : 'customer';
        });
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($this->role, self::PERMISSIONS[$permission]['roles'] ?? [], true);
    }

    public function isWorkspaceMember(): bool
    {
        return in_array($this->role, self::WORKSPACE_ROLES, true);
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? 'Customer';
    }

    /** Roles this user may give to other people. */
    public function assignableRoles(): array
    {
        if (! $this->hasPermission('users.manage')) {
            return [];
        }

        return $this->hasPermission('roles.elevate') ? array_keys(self::ROLES) : ['staff', 'customer'];
    }

    /** Owners may manage staff and customers, but never owners or admins. */
    public function canManageUser(User $other): bool
    {
        return $this->isNot($other) && in_array($other->role, $this->assignableRoles(), true);
    }
}
