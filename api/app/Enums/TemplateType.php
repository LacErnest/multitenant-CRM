<?php


namespace App\Enums;

use Spatie\Enum\Enum;

/**

 * @method static self quote()
 * @method static self order()
 * @method static self invoice()
 * @method static self purchaseOrder()
 * @method static self NDA()
 * @method static self contractor()
 * @method static self freelancer()
 * @method static self employee()
 * @method static self customer()
 *
 * @method static bool isContacts(int|string $value = null)
 * @method static bool isQuotes(int|string $value = null)
 * @method static bool isOrders(int|string $value = null)
 * @method static bool isInvoices(int|string $value = null)
 * @method static bool isPurchaseOrders(int|string $value = null)
 * @method static bool isNDA(int|string $value = null)
 * @method static bool isContractor(int|string $value = null)
 * @method static bool isFreelancer(int|string $value = null)
 * @method static bool isEmployee(int|string $value = null)
 * @method static bool isCustomer(int|string $value = null)
 */

final class TemplateType extends Enum
{
    const MAP_INDEX = [
        'quote' => 1,
        'order' => 2,
        'invoice' => 3,
        'purchaseOrder' => 4,
        'NDA'  => 5,
        'contractor'  => 6,
        'freelancer'  => 7,
        'employee' => 8,
        'customer' => 9,
    ];

    const MAP_VALUE = [
        'quote' => 'quote',
        'order' => 'order',
        'invoice' => 'invoice',
        'purchaseOrder' => 'purchase_order',
        'NDA' => 'NDA',
        'contractor' => 'contractor',
        'freelancer' => 'freelancer',
        'employee' => 'employee',
        'customer' => 'customer',
    ];
}
