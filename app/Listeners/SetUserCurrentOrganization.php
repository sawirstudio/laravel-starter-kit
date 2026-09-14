<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Filament\Events\TenantSet;

final class SetUserCurrentOrganization
{
    public function handle(TenantSet $event): void
    {
        $user = $event->getUser();

        if (! $user instanceof User) {
            return;
        }

        $user->update(['organization_id' => $event->getTenant()->getKey()]);
    }
}
