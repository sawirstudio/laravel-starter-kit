<?php

declare(strict_types=1);

use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $admin = User::factory()->create();
    config()->set('app.admins', [$admin->email]);

    Filament::setCurrentPanel('admin');
    $this->actingAs($admin);
});

test('list organizations', function (): void {
    $organizations = Organization::factory()->count(2)->create();

    $this->get(OrganizationResource::getUrl('index'))->assertOk();

    livewire(ListOrganizations::class)
        ->assertCanSeeTableRecords($organizations)
        ->assertCanRenderTableColumn('owner.name')
        ->assertCanRenderTableColumn('users_count')
        ->searchTable($organizations->first()?->slug)
        ->assertCanSeeTableRecords($organizations->take(1))
        ->assertCanNotSeeTableRecords($organizations->skip(1))
        ->filterTable('trashed', 'with')
        ->assertCanSeeTableRecords($organizations->take(1));
});

test('create organization', function (): void {
    $owner = User::factory()->create();

    $this->get(OrganizationResource::getUrl('create'))->assertOk();

    livewire(CreateOrganization::class)
        ->fillForm(['name' => 'Acme Inc', 'user_id' => $owner->id])
        ->assertSchemaStateSet(['slug' => 'acme-inc'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Organization::query()->where('slug', 'acme-inc')->firstOrFail()->owner->is($owner))->toBeTrue();
});

test('create organization validates fields', function (): void {
    $existing = Organization::factory()->create();

    livewire(CreateOrganization::class)
        ->fillForm(['name' => '', 'slug' => $existing->slug, 'user_id' => null])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required', 'slug' => 'unique', 'user_id' => 'required']);
});

test('editing does not overwrite the slug', function (): void {
    $organization = Organization::factory()->create(['slug' => 'keep-me']);

    livewire(EditOrganization::class, ['record' => $organization->getRouteKey()])
        ->fillForm(['name' => 'Renamed'])
        ->assertSchemaStateSet(['slug' => 'keep-me'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($organization->refresh()->slug)->toBe('keep-me');
});

test('edit organization', function (): void {
    $organization = Organization::factory()->create();

    $this->get(OrganizationResource::getUrl('edit', ['record' => $organization]))->assertOk();

    livewire(EditOrganization::class, ['record' => $organization->getRouteKey()])
        ->callAction('delete')
        ->assertNotified();

    expect($organization->refresh()->trashed())->toBeTrue();
});

test('edit page resolves soft deleted organizations', function (): void {
    $organization = Organization::factory()->create();
    $organization->delete();

    livewire(EditOrganization::class, ['record' => $organization->getRouteKey()])
        ->callAction('restore')
        ->assertNotified();

    expect($organization->refresh()->trashed())->toBeFalse();
});
