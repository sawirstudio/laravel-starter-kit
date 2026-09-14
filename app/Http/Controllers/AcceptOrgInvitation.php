<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\OrgInvitation;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AcceptOrgInvitation extends Controller
{
    public function __invoke(Request $request, OrgInvitation $invitation): RedirectResponse
    {
        abort_if($invitation->isExpired(), 403, 'This invitation has expired.');

        $panel = Filament::getPanel('app');
        $user = $request->user();

        if (! $user instanceof User) {
            $hasAccount = User::query()->where('email', $invitation->email)->exists();

            return redirect()->guest(
                ($hasAccount ? $panel->getLoginUrl() : $panel->getRegistrationUrl(['email' => $invitation->email])) ?? '/',
            );
        }

        abort_unless($invitation->isFor($user), 403, 'This invitation was sent to a different email address.');

        $orgUser = $invitation->accept($user);

        return redirect()->to($panel->getUrl($orgUser->organization()->firstOrFail()) ?? '/');
    }
}
