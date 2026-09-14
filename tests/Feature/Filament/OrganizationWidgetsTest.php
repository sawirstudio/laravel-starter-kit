<?php

declare(strict_types=1);

use App\Filament\App\Pages\EditOrganization;
use App\Filament\App\Widgets\MemberWidget;
use App\Models\Organization;
use App\Models\OrgInvitation;
use App\Models\OrgUser;
use App\Models\Role;
use App\Models\User;
use App\Notifications\OrgInvitationCreated;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->owner = User::factory()->create();
    $this->organization = Organization::factory()->for($this->owner, 'owner')->create();
    $this->ownerMembership = OrgUser::factory()->for($this->organization)->for($this->owner)->create();

    Filament::setCurrentPanel('app');
    $this->actingAs($this->owner);
    Filament::setTenant($this->organization, isQuiet: true);
});

test('edit team page shows the members widget', function (): void {
    $this->get(EditOrganization::getUrl(tenant: $this->organization))
        ->assertOk()
        ->assertSeeLivewire(MemberWidget::class);
});

test('widget lists members and invitations of this organization only', function (): void {
    $member = OrgUser::factory()->for($this->organization)->create();
    $invitation = OrgInvitation::factory()->for($this->organization)->create();
    $otherMember = OrgUser::factory()->create();
    $otherInvitation = OrgInvitation::factory()->create();

    livewire(MemberWidget::class)
        ->assertCanSeeTableRecords(["member-{$member->id}", "invitation-{$invitation->id}", "member-{$this->ownerMembership->id}"])
        ->assertCanNotSeeTableRecords(["member-{$otherMember->id}", "invitation-{$otherInvitation->id}"])
        ->searchTable($invitation->email)
        ->assertCanSeeTableRecords(["invitation-{$invitation->id}"])
        ->assertCanNotSeeTableRecords(["member-{$member->id}", "member-{$this->ownerMembership->id}"]);
});

test('inviting creates an invitation with roles and sends the email', function (): void {
    Notification::fake();
    $role = Role::factory()->create();

    livewire(MemberWidget::class)
        ->callTableAction('invite', data: [
            'email' => 'new@example.com',
            'roles' => [$role->id],
            'expires_at' => now()->addDays(3)->toDateTimeString(),
        ])
        ->assertHasNoFormErrors()
        ->assertNotified('Invitation sent');

    $invitation = OrgInvitation::query()->where('email', 'new@example.com')->firstOrFail();

    expect($invitation->organization->is($this->organization))->toBeTrue()
        ->and($invitation->createdBy->is($this->owner))->toBeTrue()
        ->and($invitation->hasRole($role->name))->toBeTrue();

    Notification::assertSentTo($invitation, OrgInvitationCreated::class);
});

test('invite requires a valid email', function (): void {
    livewire(MemberWidget::class)
        ->callTableAction('invite', data: ['email' => 'not-an-email', 'expires_at' => now()->addDay()->toDateTimeString()])
        ->assertHasFormErrors(['email' => 'email']);
});

test('members can be removed but not the owner', function (): void {
    $member = OrgUser::factory()->for($this->organization)->create();

    livewire(MemberWidget::class)
        ->assertTableActionHidden('remove', "member-{$this->ownerMembership->id}")
        ->assertTableActionHidden('resend', "member-{$member->id}")
        ->callTableAction('remove', "member-{$member->id}")
        ->assertNotified('Member removed');

    expect(OrgUser::query()->whereKey($member->id)->exists())->toBeFalse();
});

test('invitations can be resent and revoked', function (): void {
    Notification::fake();
    $invitation = OrgInvitation::factory()->for($this->organization)->create();

    livewire(MemberWidget::class)
        ->assertTableActionHidden('remove', "invitation-{$invitation->id}")
        ->callTableAction('resend', "invitation-{$invitation->id}")
        ->assertNotified('Invitation resent')
        ->callTableAction('revoke', "invitation-{$invitation->id}")
        ->assertNotified('Invitation revoked');

    Notification::assertSentTo($invitation, OrgInvitationCreated::class);
    expect(OrgInvitation::query()->whereKey($invitation->id)->exists())->toBeFalse();
});
