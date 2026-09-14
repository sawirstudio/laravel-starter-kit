<?php

declare(strict_types=1);

use App\Models\Organization;

test('slug is generated from the name', function (): void {
    $organization = Organization::factory()->create(['name' => 'Acme Inc', 'slug' => null]);

    expect($organization->slug)->toBe('acme-inc');
});

test('duplicate slugs get a numeric suffix', function (): void {
    $slugs = collect(range(1, 3))
        ->map(fn (): string => Organization::factory()->create(['name' => 'Acme Inc', 'slug' => null])->slug);

    expect($slugs->all())->toBe(['acme-inc', 'acme-inc-2', 'acme-inc-3']);
});

test('soft deleted records still reserve their slug', function (): void {
    Organization::factory()->create(['name' => 'Acme', 'slug' => null])->delete();

    expect(Organization::factory()->create(['name' => 'Acme', 'slug' => null])->slug)->toBe('acme-2');
});

test('an explicit slug is kept and renaming does not change it', function (): void {
    $organization = Organization::factory()->create(['name' => 'Acme', 'slug' => 'custom']);
    $organization->update(['name' => 'Renamed']);

    expect($organization->refresh()->slug)->toBe('custom');
});

test('generate unique slug can ignore a record', function (): void {
    $organization = Organization::factory()->create(['slug' => 'acme']);

    expect(Organization::generateUniqueSlug('Acme', $organization->id))->toBe('acme')
        ->and(Organization::generateUniqueSlug('Acme'))->toBe('acme-2');
});
