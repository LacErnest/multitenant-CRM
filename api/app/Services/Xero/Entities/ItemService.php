<?php


namespace App\Services\Xero\Entities;

use App\Enums\Country;
use App\Enums\PriceModifierQuantityType;
use App\Enums\PriceModifierType;
use App\Models\Item;
use Illuminate\Support\Facades\App;
use phpDocumentor\Reflection\Types\Boolean;
use \XeroAPI\XeroPHP\Models\Accounting\LineItem;
use \XeroAPI\XeroPHP\Api\AccountingApi;

class ItemService extends BaseEntity
{
    private string $tenant_id;
    private AccountingApi $apiInstance;
    const DEFAULT_ACCOUNT = '200';
    const DEFAULT_TAX_TYPE = 'OUTPUT';

    public function __construct(AccountingApi $apiInstance, string $tenant_id)
    {
        $this->tenant_id    = $tenant_id;
        $this->apiInstance  = $apiInstance;
    }

    /**
     * @param Item $item
     * @param bool $manualInput
     * @param float $currencyRate
     * @param string $account
     * @param string $taxType
     * @return LineItem
     */
    public function getLine(
        Item $item,
        bool $manualInput,
        float $currencyRate,
        string $account,
        string $taxType
    ): LineItem {
        $line = App::make(LineItem::class);
        $discount = 0;
        $surplus = 0;
        $priceModifiers = $item->priceModifiers;

        foreach ($priceModifiers as $modifier) {
            $discount += $modifier->type == PriceModifierType::discount()->getIndex() ? $modifier->quantity : 0;
            $surplus += $modifier->type == PriceModifierType::charge()->getIndex() ? $modifier->quantity : 0;
        }

        if ($manualInput) {
            $unitPrice = $item->unit_price;
        } else {
            $unitPrice = round($item->unit_price * $currencyRate, 2);
        }

        $line->setDescription($item->service_name ? $item->service_name : $item->description)
          ->setQuantity($item->quantity ?? null)
          ->setUnitAmount($surplus == 0 ? decFormat($unitPrice) : decFormat($unitPrice * ((100 + $surplus)/100)))
          ->setAccountCode($account)
          ->setTaxType($taxType)
          ->setDiscountRate($discount == 0 ? null : $discount);

        return $line;
    }

    /**
     * @param object $items
     * @param object $modifiers
     * @param bool $manualInput
     * @param float $price
     * @param float $currencyRate
     * @param string|null $account
     * @param string|null $taxType
     * @return array
     */
    public function getLines(
        object $items,
        object $modifiers,
        bool $manualInput,
        float $price,
        float $currencyRate,
        ?string $account,
        ?string $taxType
    ): array {
        $lineItems = [];

        if ($account === null) {
            $account = self::DEFAULT_ACCOUNT;
        }

        if ($taxType === null) {
            $taxType = self::DEFAULT_TAX_TYPE;
        }

        foreach ($items as $item) {
            array_push($lineItems, $this->getLine($item, $manualInput, $currencyRate, $account, $taxType));
        }

        foreach ($modifiers as $modifier) {
            $quantityType = PriceModifierQuantityType::isPercentage($modifier->quantity_type) ? ' %' : '';
            if (PriceModifierQuantityType::isFixed($modifier->quantity_type)) {
                $totalModifierPrice = $modifier->type == PriceModifierType::discount()->getIndex() ?
                0  - ($modifier->quantity) :
                $modifier->quantity;
            } else {
                $totalModifierPrice = $modifier->type == PriceModifierType::discount()->getIndex() ?
                0  - ($price * ($modifier->quantity/100)) :
                $price * ((100 + $modifier->quantity)/100) - $price;
            }

            $convertedPrice = ceiling($totalModifierPrice * $currencyRate, 2);

            $line = App::make(LineItem::class);
            $line->setDescription($modifier->description . ' ' . $modifier->quantity . $quantityType)
            ->setQuantity(1)
            ->setUnitAmount($manualInput ? $totalModifierPrice : decFormat($convertedPrice))
            ->setAccountCode($account)
            ->setTaxType($taxType);

            array_push($lineItems, $line);
        }

        return $lineItems;
    }
}
