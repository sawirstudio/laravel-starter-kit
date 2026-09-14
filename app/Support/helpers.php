<?php

declare(strict_types=1);

namespace App\Support;

use BackedEnum;
use UnitEnum;

/**
 * Return the backing value of a backed enum, the case name of a pure enum,
 * or the string itself.
 */
function enum_value(string|UnitEnum $value): string|int
{
    return match (true) {
        $value instanceof BackedEnum => $value->value,
        $value instanceof UnitEnum => $value->name,
        default => $value,
    };
}
