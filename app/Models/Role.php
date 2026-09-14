<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasPermissions;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name'])]
final class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    use HasPermissions;
    use SoftDeletes;

    /**
     * @return MorphToMany<User, $this, Roleable>
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'roleable')
            ->using(Roleable::class)
            ->withTimestamps();
    }
}
