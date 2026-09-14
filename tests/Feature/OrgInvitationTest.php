<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\OrgInvitation;
use App\Models\Role;
use App\Models\User;
use App\Notifications\OrgInvitationCreated;
use Filament\Facades\Filament;

test('accepting creates the membership with the invitation roles', function (): void {
    $role = Role::factory()->create();
    $invitation = OrgInvitation::factory()->create(['email' => 'Jane@Example.com']);
    $invitation->roles()->attach($role);
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $orgUser = $invitation->accept($user);

    expect($orgUser->organization->is($invitation->organization))->toBeTrue()
        ->and($orgUser->user->is($user))->toBeTrue()
        ->and($orgUser->hasRole($role->name))->toBeTrue()
        ->and($user->refresh()->organization_id)->toBe($invitation->organization_id)
        ->and($invitation->refresh()->trashed())->toBeTrue();
});

test('accepting twice does not duplicate the membership', function (): void {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $first = OrgInvitation::factory()->for($organization)->create(['email' => $user->email]);
    $second = OrgInvitation::factory()->for($organization)->create(['email' => $user->email]);

    $first->accept($user);
    $second->accept($user);

    expect($organization->orgUsers()->count())->toBe(1);
});

test('pending scope excludes expired invitations', function (): void {
    OrgInvitation::factory()->create(['expires_at' => now()->subMinute()]);
    $pending = OrgInvitation::factory()->create(['expires_at' => now()->addMinute()]);

    expect(OrgInvitation::query()->pending()->pluck('id')->all())->toBe([$pending->id]);
});

test('the invitation email links to the signed accept url', function (): void {
    $invitation = OrgInvitation::factory()->create();

    $mail = (new OrgInvitationCreated)->toMail($invitation);

    expect($mail->actionUrl)->toBe($invitation->acceptUrl())
        ->and($mail->subject)->toContain($invitation->organization->name)
        ->and((new OrgInvitationCreated)->via($invitation))->toBe(['mail']);
});

test('guests without an account are sent to register with the email prefilled', function (): void {
    $invitation = OrgInvitation::factory()->create();
    $registerUrl = Filament::getPanel('app')->getRegistrationUrl(['email' => $invitation->email]);

    $this->get($invitation->acceptUrl())->assertRedirect($registerUrl);

    $this->get($registerUrl)
        ->assertOk()
        ->assertSee($invitation->email);
});

test('guests are sent to login and come back to accept', function (): void {
    $invitation = OrgInvitation::factory()->create();
    $user = User::factory()->create(['email' => $invitation->email]);

    $this->get($invitation->acceptUrl())
        ->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    $this->actingAs($user)
        ->get($invitation->acceptUrl())
        ->assertRedirect(Filament::getPanel('app')->getUrl($invitation->organization));

    expect($invitation->organization->users->first()?->is($user))->toBeTrue();
});

test('accepting requires the invited email address', function (): void {
    $invitation = OrgInvitation::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get($invitation->acceptUrl())
        ->assertForbidden();
});

test('expired invitations cannot be accepted', function (): void {
    $invitation = OrgInvitation::factory()->create(['expires_at' => now()->addMinute()]);
    $url = $invitation->acceptUrl();
    $user = User::factory()->create(['email' => $invitation->email]);

    $this->travel(2)->minutes();

    $this->actingAs($user)->get($url)->assertForbidden();
});

test('tampered links are rejected', function (): void {
    $invitation = OrgInvitation::factory()->create();
    $user = User::factory()->create(['email' => $invitation->email]);

    $this->actingAs($user)
        ->get(route('org-invitations.accept', ['invitation' => $invitation]))
        ->assertForbidden();
});

test('old expired and retired invitations are pruned', function (): void {
    $freshPending = OrgInvitation::factory()->create(['expires_at' => now()->addDay()]);
    $recentlyExpired = OrgInvitation::factory()->create(['expires_at' => now()->subDays(29)]);
    $oldExpired = OrgInvitation::factory()->create(['expires_at' => now()->subDays(31)]);
    $recentlyAccepted = OrgInvitation::factory()->create(['expires_at' => now()->addDay()]);
    $recentlyAccepted->delete();

    $oldAccepted = OrgInvitation::factory()->create(['expires_at' => now()->addDay(), 'deleted_at' => now()->subDays(31)]);

    $this->artisan('model:prune', ['--model' => OrgInvitation::class])->assertSuccessful();

    $remaining = OrgInvitation::query()->withTrashed()->pluck('id')->sort()->values()->all();

    expect($remaining)->toBe([$freshPending->id, $recentlyExpired->id, $recentlyAccepted->id])
        ->and(OrgInvitation::query()->withTrashed()->whereKey([$oldExpired->id, $oldAccepted->id])->exists())->toBeFalse();
});
