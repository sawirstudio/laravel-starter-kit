<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasRoles;
use Database\Factories\OrgUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Table(name: 'org_users')]
#[Fillable(['user_id', 'organization_id'])]
final class OrgUser extends Pivot
{
    /** @use HasFactory<OrgUserFactory> */
    use HasFactory;

    use HasRoles;
    use SoftDeletes;

    public $incrementing = true;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
