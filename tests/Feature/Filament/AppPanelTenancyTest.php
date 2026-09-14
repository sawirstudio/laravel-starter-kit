<?php

declare(strict_types=1);

use App\Filament\App\Pages\EditOrganization;
use App\Filament\App\Pages\RegisterOrganization;
use App\Models\Organization;
use App\Models\OrgUser;
use App\Models\User;
use Filament\Events\TenantSet;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('app');
});

test('register organization creates tenant and membership', function (): void {
    $user = User::factory()->create(['name' => 'Jane']);
    $this->actingAs($user);

    livewire(RegisterOrganization::class)
        ->assertSchemaStateSet(['name' => "Jane's Team"])
        ->fillForm(['name' => 'Acme Inc'])
        ->call('register')
        ->assertHasNoFormErrors();

    $organization = Organization::query()->where('slug', 'acme-inc')->firstOrFail();

    expect($organization->owner->is($user))->toBeTrue()
        ->and(OrgUser::query()->where('user_id', $user->id)->where('organization_id', $organization->id)->exists())->toBeTrue();
});

test('register organization requires a name', function (): void {
    $this->actingAs(User::factory()->create());

    livewire(RegisterOrganization::class)
        ->fillForm(['name' => ''])
        ->call('register')
        ->assertHasFormErrors(['name' => 'required']);
});

test('edit organization profile', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->for($user, 'owner')->create();
    OrgUser::factory()->for($organization)->for($user)->create();

    $this->actingAs($user);
    Filament::setTenant($organization, isQuiet: true);

    livewire(EditOrganization::class)
        ->assertSchemaStateSet(['name' => $organization->name])
        ->fillForm(['name' => 'Renamed Team'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($organization->refresh()->name)->toBe('Renamed Team');
});

test('setting a tenant remembers the current organization', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($organization);

    expect($user->refresh()->organization_id)->toBe($organization->id);
});

test('app dashboard loads for a member with a default tenant', function (): void {
    $user = User::factory()->create();
    $organization = Organization::factory()->for($user, 'owner')->create();
    OrgUser::factory()->for($organization)->for($user)->create();
    $user->update(['organization_id' => $organization->id]);

    $this->actingAs($user)
        ->get('/app/'.$organization->slug)
        ->assertOk();
});

test('tenant listener ignores non user accounts', function (): void {
    $organization = Organization::factory()->create();

    event(new TenantSet($organization, $organization));

    expect(User::query()->whereNotNull('organization_id')->exists())->toBeFalse();
});
