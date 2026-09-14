<?php

declare(strict_types=1);

use App\Http\Controllers\AcceptOrgInvitation;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): Factory|View => view('welcome'));

Route::get('/invitations/{invitation}/accept', AcceptOrgInvitation::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('org-invitations.accept');
