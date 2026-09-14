<?php

declare(strict_types=1);

namespace App\Filament\App\Auth;

use Filament\Auth\Pages\Register as BaseRegister;

final class Register extends BaseRegister
{
    /**
     * Prefill the email when arriving from an invitation link.
     */
    public function afterFill(): void
    {
        $email = request()->query('email');

        if (is_string($email) && $email !== '') {
            $this->form->fill(['email' => $email]);
        }
    }
}
