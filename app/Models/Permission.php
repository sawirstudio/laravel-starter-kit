<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

#[Fillable(['name'])]
final class Permission extends Model
{
    /** @use HasFactory<PermissionFactory> */
    use HasFactory;

    /**
     * @return MorphToMany<Role, $this, Permissionable>
     */
    public function roles(): MorphToMany
    {
        return $this->morphedByMany(Role::class, 'permissionable')
            ->using(Permissionable::class)
            ->withTimestamps();
    }

    /**
     * @return MorphToMany<User, $this, Permissionable>
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'permissionable')
            ->using(Permissionable::class)
            ->withTimestamps();
    }
}
