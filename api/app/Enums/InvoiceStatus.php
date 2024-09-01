<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self draft()
 * @method static self approval()
 * @method static self authorised()
 * @method static self submitted()
 * @method static self paid()
 * @method static self unpaid()
 * @method static self cancelled()
 * @method static self rejected()
 * @method static self partial_paid()
 *
 * @method static bool isDraft(int|string $value = null)
 * @method static bool isApproval(int|string $value = null)
 * @method static bool isAuthorised(int|string $value = null)
 * @method static bool isSubmitted(int|string $value = null)
 * @method static bool isPaid(int|string $value = null)
 * @method static bool isUnpaid(int|string $value = null)
 * @method static bool isCancelled(int|string $value = null)
 * @method static bool isRejected(int|string $value = null)
 * @method static bool isPartial_paid(int|string $value = null)
 */

final class InvoiceStatus extends Enum
{
    const MAP_INDEX = [
        'draft' => 0,
        'approval' => 1,
        'authorised' => 2,
        'submitted' => 3,
        'paid' => 4,
        'unpaid' => 5,
        'cancelled' => 6,
        'rejected' => 7,
        'partial_paid' => 8,
    ];

    const MAP_VALUE = [
        'draft' => 'Draft',
        'approval' => 'Approval',
        'authorised' => 'Authorised',
        'submitted' => 'Submitted',
        'paid' => 'Paid',
        'unpaid' => 'Unpaid',
        'cancelled' => 'Cancelled',
        'rejected' => 'Rejected',
        'partial_paid' => 'Partial Paid',
    ];
}
