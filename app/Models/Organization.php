<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'user_id'])]
final class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    use HasSlug;
    use SoftDeletes;

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<OrgUser, $this>
     */
    public function orgUsers(): HasMany
    {
        return $this->hasMany(OrgUser::class);
    }

    /**
     * @return BelongsToMany<User, $this, OrgUser>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'org_users')
            ->using(OrgUser::class)
            ->withPivot('id')
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

    /**
     * @return HasMany<OrgInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(OrgInvitation::class);
    }
}
