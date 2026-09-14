<?php

declare(strict_types=1);

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $admin = User::factory()->create();
    config()->set('app.admins', [$admin->email]);

    Filament::setCurrentPanel('admin');
    $this->actingAs($admin);
});

test('non admins cannot access the panel', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(UserResource::getUrl('index'))
        ->assertForbidden();
});

test('list users', function (): void {
    $users = User::factory()->count(3)->create();

    $this->get(UserResource::getUrl('index'))->assertOk();

    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()?->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1));
});

test('create user', function (): void {
    $this->get(UserResource::getUrl('create'))->assertOk();

    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::query()->where('email', 'jane@example.com')->exists())->toBeTrue();
});

test('create user validates required fields', function (): void {
    livewire(CreateUser::class)
        ->fillForm(['name' => '', 'email' => '', 'password' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required', 'email' => 'required', 'password' => 'required']);
});

test('edit user', function (): void {
    $user = User::factory()->create();

    $this->get(UserResource::getUrl('edit', ['record' => $user]))->assertOk();

    livewire(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertSchemaStateSet(['name' => $user->name, 'email' => $user->email])
        ->fillForm(['name' => 'Renamed', 'password' => 'new-password'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh()->name)->toBe('Renamed');
});

test('edit page resolves soft deleted users', function (): void {
    $user = User::factory()->create();
    $user->delete();

    livewire(EditUser::class, ['record' => $user->getRouteKey()])
        ->callAction('restore')
        ->assertNotified();

    expect($user->refresh()->trashed())->toBeFalse();
});
