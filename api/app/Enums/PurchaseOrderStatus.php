<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self draft()
 * @method static self submitted()
 * @method static self rejected()
 * @method static self authorised()
 * @method static self billed()
 * @method static self paid()
 * @method static self cancelled()
 * @method static self completed()
 *
 * @method static bool isDraft(int|string $value = null)
 * @method static bool isSubmitted(int|string $value = null)
 * @method static bool isRejected(int|string $value = null)
 * @method static bool isAuthorised(int|string $value = null)
 * @method static bool isBilled(int|string $value = null)
 * @method static bool isPaid(int|string $value = null)
 * @method static bool isCancelled(int|string $value = null)
 * @method static bool isCompleted(int|string $value = null)
 */

final class PurchaseOrderStatus extends Enum
{
    const MAP_INDEX = [
        'draft' => 0,
        'submitted' => 1,
        'rejected' => 2,
        'authorised' => 3,
        'billed' => 4,
        'paid' => 5,
        'cancelled' => 6,
        'completed' => 7,
    ];

    const MAP_VALUE = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'rejected' => 'Rejected',
        'authorised' => 'Authorised',
        'billed' => 'Billed',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
        'completed' => 'Completed',
    ];
}
