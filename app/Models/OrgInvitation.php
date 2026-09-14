<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasRoles;
use Carbon\CarbonInterface;
use Database\Factories\OrgInvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * @property CarbonInterface $expires_at
 */
#[Fillable(['email', 'expires_at', 'organization_id', 'created_by_id'])]
final class OrgInvitation extends Model
{
    /** @use HasFactory<OrgInvitationFactory> */
    use HasFactory;

    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isFor(User $user): bool
    {
        return mb_strtolower($this->email) === mb_strtolower($user->email);
    }

    public function acceptUrl(): string
    {
        return URL::temporarySignedRoute('org-invitations.accept', $this->expires_at, ['invitation' => $this]);
    }

    /**
     * Turn the invitation into a membership for the given user, copying the
     * invitation's roles onto the membership, and retire the invitation.
     */
    public function accept(User $user): OrgUser
    {
        return DB::transaction(function () use ($user): OrgUser {
            $organization = $this->organization()->firstOrFail();

            $orgUser = $organization->orgUsers()->firstOrCreate(['user_id' => $user->id]);
            $orgUser->roles()->syncWithoutDetaching($this->roles->modelKeys());

            $user->forceFill(['organization_id' => $organization->getKey()])->save();

            $this->delete();

            return $orgUser;
        });
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('expires_at', '>', now());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
