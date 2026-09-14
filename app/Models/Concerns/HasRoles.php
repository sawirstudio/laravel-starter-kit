<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Roleable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use UnitEnum;

use function App\Support\enum_value;

trait HasRoles
{
    use HasPermissions {
        hasPermission as hasDirectPermission;
    }

    /**
     * @return MorphToMany<Role, $this, Roleable>
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(Role::class, 'roleable')
            ->using(Roleable::class)
            ->withTimestamps();
    }

    public function hasRole(string|UnitEnum $name): bool
    {
        return $this->roles->contains('name', enum_value($name));
    }

    public function hasPermission(string|UnitEnum $name): bool
    {
        return $this->all_permissions->contains('name', enum_value($name));
    }

    /**
     * Direct permissions merged with permissions inherited through roles.
     *
     * @return Attribute<Collection<int, Permission>, never>
     */
    protected function allPermissions(): Attribute
    {
        return Attribute::get(fn (): Collection => $this->permissions
            ->merge($this->roles->flatMap(fn (Role $role): Collection => $role->permissions))
            ->unique('id')
            ->values());
    }

    /**
     * Eager load everything the all_permissions accessor needs.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function withAllPermissions(Builder $query): void
    {
        $query->with(['permissions', 'roles.permissions']);
    }

    /**
     * Only models holding the permission directly or through a role.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function wherePermission(Builder $query, string|UnitEnum $name): void
    {
        $name = enum_value($name);

        $query->where(function (Builder $query) use ($name): void {
            $query
                ->whereHas('permissions', fn (Builder $q): Builder => $q->where('name', $name))
                ->orWhereHas('roles.permissions', fn (Builder $q): Builder => $q->where('name', $name));
        });
    }
}
