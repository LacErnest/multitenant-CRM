<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self draft()
 * @method static self sent()
 * @method static self declined()
 * @method static self ordered()
 * @method static self invoiced()
 * @method static self cancelled()
 *
 * @method static bool isDraft(int|string $value = null)
 * @method static bool isSent(int|string $value = null)
 * @method static bool isDeclined(int|string $value = null)
 * @method static bool isOrdered(int|string $value = null)
 * @method static bool isInvoiced(int|string $value = null)
 * @method static bool isCancelled(int|string $value = null)
 */

final class QuoteStatus extends Enum
{
    const MAP_INDEX = [
        'draft' => 0,
        'sent' => 1,
        'declined' => 2,
        'ordered' => 3,
        'invoiced' => 4,
        'cancelled' => 5,
    ];

    const MAP_VALUE = [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'declined' => 'Declined',
        'ordered' => 'Ordered',
        'invoiced' => 'Invoiced',
        'cancelled' => 'Cancelled',
    ];
}
