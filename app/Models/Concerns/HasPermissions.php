<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Permission;
use App\Models\Permissionable;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use UnitEnum;

use function App\Support\enum_value;

trait HasPermissions
{
    /**
     * @return MorphToMany<Permission, $this, Permissionable>
     */
    public function permissions(): MorphToMany
    {
        return $this->morphToMany(Permission::class, 'permissionable')
            ->using(Permissionable::class)
            ->withTimestamps();
    }

    public function hasPermission(string|UnitEnum $name): bool
    {
        return $this->permissions->contains('name', enum_value($name));
    }
}
