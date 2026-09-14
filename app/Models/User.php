<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasRoles;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Override;
use Spatie\OneTimePasswords\Models\Concerns\HasOneTimePasswords;

#[Hidden(['password', 'remember_token'])]
final class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants, MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasOneTimePasswords;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    #[Override]
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->is_super_admin;
        }

        return true;
    }

    /**
     * @return Collection<int, Organization>
     */
    #[Override]
    public function getTenants(Panel $panel): Collection
    {
        return $this->organizations;
    }

    #[Override]
    public function canAccessTenant(Model $tenant): bool
    {
        return $tenant instanceof Organization
            && $this->organizations->contains(fn (Organization $organization): bool => $organization->is($tenant));
    }

    #[Override]
    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->currentOrganization;
    }

    /**
     * @return HasMany<Organization, $this>
     */
    public function ownedOrganizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    /**
     * @return HasMany<OrgUser, $this>
     */
    public function orgUsers(): HasMany
    {
        return $this->hasMany(OrgUser::class);
    }

    /**
     * @return BelongsToMany<Organization, $this, OrgUser>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'org_users')
            ->using(OrgUser::class)
            ->withPivot('id')
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function currentOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    /**
     * @return HasMany<OrgInvitation, $this>
     */
    public function sentInvitations(): HasMany
    {
        return $this->hasMany(OrgInvitation::class, 'created_by_id');
    }

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

    /**
     * @return Attribute<bool, never>
     */
    protected function isSuperAdmin(): Attribute
    {
        return Attribute::get(fn (): bool => in_array($this->email, config()->array('app.admins')));
    }
}
