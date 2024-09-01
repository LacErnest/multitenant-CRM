<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * Class ContactGenderTypes
 *
 * @method static self paid()
 * @method static self confirmed()
 * @method static self canceled()
 *
 * @method static bool isPaid(int|string $value = null)
 * @method static bool isConfirmed(int|string $value = null)
 * @method static bool isCanceled(int|string $value = null)
 */
final class CommissionPaymentLogStatus extends Enum
{
    const MAP_INDEX = [
        'paid' => 0,
        'confirmed' => 1,
        'canceled' => 2,
    ];

    const MAP_VALUE = [
        'paid' => 'Paid',
        'confirmed' => 'Confirmed',
        'canceled' => 'Canceled',
    ];
}
