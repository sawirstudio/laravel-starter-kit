<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

test('user has roles and permissions', function (): void {
    $user = User::factory()->create();
    $role = Role::factory()->create();
    $permission = Permission::factory()->create();

    $user->roles()->attach($role);
    $user->permissions()->attach($permission);
    $role->permissions()->attach($permission);

    expect($user->roles->first()?->is($role))->toBeTrue()
        ->and($user->permissions->first()?->is($permission))->toBeTrue()
        ->and($role->permissions->first()?->is($permission))->toBeTrue()
        ->and($role->users->first()?->is($user))->toBeTrue()
        ->and($permission->roles->first()?->is($role))->toBeTrue()
        ->and($permission->users->first()?->is($user))->toBeTrue();
});

test('pivots resolve their related models', function (): void {
    $user = User::factory()->create();
    $role = Role::factory()->create();
    $permission = Permission::factory()->create();

    $user->roles()->attach($role);
    $user->permissions()->attach($permission);

    $roleable = $user->roles()->first()?->pivot;
    $permissionable = $user->permissions()->first()?->pivot;

    expect($roleable?->role?->is($role))->toBeTrue()
        ->and($roleable?->roleable?->is($user))->toBeTrue()
        ->and($permissionable?->permission?->is($permission))->toBeTrue()
        ->and($permissionable?->permissionable?->is($user))->toBeTrue();
});

test('has role', function (): void {
    $user = User::factory()->create();
    $user->roles()->attach(Role::factory()->create(['name' => 'editor']));

    expect($user->hasRole('editor'))->toBeTrue()
        ->and($user->hasRole('admin'))->toBeFalse();
});

test('has permission directly', function (): void {
    $user = User::factory()->create();
    $user->permissions()->attach(Permission::factory()->create(['name' => 'posts.create']));

    expect($user->hasPermission('posts.create'))->toBeTrue()
        ->and($user->hasPermission('posts.delete'))->toBeFalse();
});

test('has permission through role', function (): void {
    $role = Role::factory()->create();
    $role->permissions()->attach(Permission::factory()->create(['name' => 'posts.publish']));

    $user = User::factory()->create();
    $user->roles()->attach($role);

    expect($user->hasPermission('posts.publish'))->toBeTrue()
        ->and($role->hasPermission('posts.publish'))->toBeTrue()
        ->and($role->hasPermission('posts.delete'))->toBeFalse();
});

test('all permissions merges direct and role permissions', function (): void {
    $shared = Permission::factory()->create(['name' => 'shared']);
    $direct = Permission::factory()->create(['name' => 'direct']);
    $viaRole = Permission::factory()->create(['name' => 'via-role']);

    $role = Role::factory()->create();
    $role->permissions()->attach([$shared->id, $viaRole->id]);

    $user = User::factory()->create();
    $user->roles()->attach($role);
    $user->permissions()->attach([$shared->id, $direct->id]);

    expect($user->all_permissions->pluck('name')->sort()->values()->all())
        ->toBe(['direct', 'shared', 'via-role']);
});

test('with all permissions eager loads roles and permissions', function (): void {
    $role = Role::factory()->create();
    $role->permissions()->attach(Permission::factory()->create());
    $user = User::factory()->create();
    $user->roles()->attach($role);

    $loaded = User::query()->withAllPermissions()->findOrFail($user->id);

    expect($loaded->relationLoaded('permissions'))->toBeTrue()
        ->and($loaded->relationLoaded('roles'))->toBeTrue()
        ->and($loaded->roles->first()?->relationLoaded('permissions'))->toBeTrue();
});

test('where permission finds direct and role holders', function (): void {
    $permission = Permission::factory()->create(['name' => 'posts.publish']);
    $role = Role::factory()->create();
    $role->permissions()->attach($permission);

    $direct = User::factory()->create();
    $direct->permissions()->attach($permission);

    $viaRole = User::factory()->create();
    $viaRole->roles()->attach($role);

    User::factory()->create();

    expect(User::query()->wherePermission('posts.publish')->pluck('id')->sort()->values()->all())
        ->toBe([$direct->id, $viaRole->id]);
});

enum TestPermission: string
{
    case Publish = 'posts.publish';
}

enum TestRole: string
{
    case Editor = 'editor';
}

test('accepts backed enums', function (): void {
    $role = Role::factory()->create(['name' => 'editor']);
    $role->permissions()->attach(Permission::factory()->create(['name' => 'posts.publish']));

    $user = User::factory()->create();
    $user->roles()->attach($role);

    expect($user->hasRole(TestRole::Editor))->toBeTrue()
        ->and($user->hasPermission(TestPermission::Publish))->toBeTrue()
        ->and($role->hasPermission(TestPermission::Publish))->toBeTrue()
        ->and(User::query()->wherePermission(TestPermission::Publish)->pluck('id')->all())->toBe([$user->id]);
});

enum TestUnitRole
{
    case Editor;
}

test('accepts unit enums using the case name', function (): void {
    $user = User::factory()->create();
    $user->roles()->attach(Role::factory()->create(['name' => 'Editor']));

    expect($user->hasRole(TestUnitRole::Editor))->toBeTrue();
});
