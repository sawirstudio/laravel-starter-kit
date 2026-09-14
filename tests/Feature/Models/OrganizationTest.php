<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\OrgInvitation;
use App\Models\OrgUser;
use App\Models\Role;
use App\Models\User;

test('organization belongs to an owner', function (): void {
    $owner = User::factory()->create();
    $organization = Organization::factory()->for($owner, 'owner')->create();

    expect($organization->owner->is($owner))->toBeTrue()
        ->and($owner->ownedOrganizations->first()?->is($organization))->toBeTrue();
});

test('organization has members through org users', function (): void {
    $organization = Organization::factory()->create();
    $member = User::factory()->create();
    $orgUser = OrgUser::factory()->for($organization)->for($member)->create();

    expect($organization->users->first()?->is($member))->toBeTrue()
        ->and($organization->orgUsers->first()?->is($orgUser))->toBeTrue()
        ->and($member->organizations->first()?->is($organization))->toBeTrue()
        ->and($member->orgUsers->first()?->is($orgUser))->toBeTrue()
        ->and($orgUser->user->is($member))->toBeTrue()
        ->and($orgUser->organization->is($organization))->toBeTrue();
});

test('soft deleted memberships are excluded', function (): void {
    $orgUser = OrgUser::factory()->create();
    $orgUser->delete();

    expect($orgUser->organization->users)->toHaveCount(0)
        ->and($orgUser->user->organizations)->toHaveCount(0);
});

test('org user can hold roles', function (): void {
    $orgUser = OrgUser::factory()->create();
    $orgUser->roles()->attach(Role::factory()->create(['name' => 'admin']));

    expect($orgUser->hasRole('admin'))->toBeTrue();
});

test('invitation belongs to organization and creator', function (): void {
    $organization = Organization::factory()->create();
    $creator = User::factory()->create();
    $invitation = OrgInvitation::factory()->for($organization)->for($creator, 'createdBy')->create();
    $invitation->roles()->attach(Role::factory()->create(['name' => 'member']));

    expect($invitation->organization->is($organization))->toBeTrue()
        ->and($invitation->createdBy->is($creator))->toBeTrue()
        ->and($invitation->expires_at?->isFuture())->toBeTrue()
        ->and($invitation->hasRole('member'))->toBeTrue()
        ->and($organization->invitations->first()?->is($invitation))->toBeTrue()
        ->and($creator->sentInvitations->first()?->is($invitation))->toBeTrue();
});
