<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class UserResource extends JsonApiResource
{
    /**
     * The resource's attributes.
     */
    public $attributes = [
        'name',
        'email',
        'email_verified_at',
    ];

    /**
     * The resource's relationships.
     */
    public $relationships = [
        // ...
    ];
}
