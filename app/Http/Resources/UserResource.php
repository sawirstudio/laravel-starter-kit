<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\JsonApi\JsonApiResource;

final class UserResource extends JsonApiResource
{
    /**
     * The resource's attributes.
     */
    /** @var list<string> */
    public array $attributes = [
        'name',
        'email',
        'email_verified_at',
    ];

    /**
     * The resource's relationships.
     */
    /** @var list<string> */
    public array $relationships = [
        // ...
    ];
}
