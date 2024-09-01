<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self draft()
 * @method static self active()
 * @method static self delivered()
 * @method static self invoiced()
 * @method static self cancelled()
 *
 * @method static bool isDraft(int|string $value = null)
 * @method static bool isActive(int|string $value = null)
 * @method static bool isDelivered(int|string $value = null)
 * @method static bool isInvoiced(int|string $value = null)
 * @method static bool isCancelled(int|string $value = null)
 */

final class OrderStatus extends Enum
{

    const MAP_INDEX = [
        'draft' => 0,
        'active' => 1,
        'delivered' => 2,
        'invoiced' => 3,
        'cancelled' => 4,
    ];

    const MAP_VALUE = [
        'draft' => 'Draft',
        'active' => 'Active',
        'delivered' => 'Delivered',
        'invoiced' => 'Invoiced',
        'cancelled' => 'Cancelled',
    ];
}
