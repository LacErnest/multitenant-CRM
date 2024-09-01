<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self active()
 * @method static self potential()
 * @method static self inactive()
 * @method static self archived()
 *
 * @method static bool isActive(int|string $value = null)
 * @method static bool isPotential(int|string $value = null)
 * @method static bool isInactive(int|string $value = null)
 * @method static bool isArchived(int|string $value = null)
 */



final class EmployeeStatus extends Enum
{
    const MAP_INDEX = [
        'active' => 0,
        'potential' => 1,
        'inactive' => 2,
        'archived' => 3,
    ];

    const MAP_VALUE = [
        'active' => 'Active',
        'potential' => 'Potential',
        'inactive' => 'Inactive',
        'archived' => 'Archived',
    ];
}
