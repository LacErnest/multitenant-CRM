<?php

namespace App\Enums;

use Spatie\Enum\Enum;


/**
 * @method static self draft()
 * @method static self submitted()
 * @method static self deleted()
 * @method static self authorised()
 * @method static self paid()
 * @method static self voided()
 *
 * @method static bool isDraft(int|string $value = null)
 * @method static bool isSubmitted(int|string $value = null)
 * @method static bool isDeleted(int|string $value = null)
 * @method static bool isAuthorised(int|string $value = null)
 * @method static bool isPaid(int|string $value = null)
 * @method static bool isVoided(int|string $value = null)
 */

final class CreditNoteStatus extends Enum
{
    const MAP_INDEX = [
        'draft' => 0,
        'submitted' => 1,
        'deleted' => 2,
        'authorised' => 3,
        'paid' => 4,
        'voided' => 5,
    ];

    const MAP_VALUE = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'deleted' => 'Deleted',
        'authorised' => 'Authorised',
        'paid' => 'Paid',
        'voided' => 'Voided',
    ];
}
