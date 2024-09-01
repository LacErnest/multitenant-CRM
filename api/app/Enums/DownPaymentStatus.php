<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self default()
 * @method static self never()
 * @method static self always()
 *
 * @method static bool isDefault(int|string $value = null)
 * @method static bool isNever(int|string $value = null)
 * @method static bool isAlways(int|string $value = null)
 */

final class DownPaymentStatus extends Enum
{
    const MAP_INDEX = [
        'default' => 1,
        'never' => 2,
        'always' => 3,
    ];

    const MAP_VALUE = [
        'default' => 'Default',
        'never' => 'Never add Down Payment',
        'always' => 'Always add Down Payment',
    ];
}
