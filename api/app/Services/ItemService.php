<?php


namespace App\Services;


use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Contact;
use App\Models\Item;
use App\Models\MasterShadow;
use App\Models\Order;
use App\Models\PriceModifier;
use App\Models\PurchaseOrder;
use App\Models\Service;
use App\Repositories\ItemRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tenancy\Facades\Tenancy;

class ItemService
{
    protected ItemRepository $itemRepository;

    protected float $currencyRateEurToUSD;

    public function __construct(ItemRepository $itemRepository)
    {
        $this->itemRepository = $itemRepository;
        $this->currencyRateEurToUSD = getCurrencyRates()['rates']['USD'];
    }

    public function createItem(
        string $companyId,
        string $entityId,
        array $attributes,
        string $class
    ): Item {
        $entity = $class::with('priceModifiers')->findOrFail($entityId);
        $attributes['entity_id'] = $entityId;
        $attributes['entity_type'] = $class;
        $modifiers = [];
        if (array_key_exists('price_modifiers', $attributes)) {
            $modifiers = $attributes['price_modifiers'];
            unset($attributes['price_modifiers']);
        }
        if (
          !$entity->manual_input && $class != PurchaseOrder::class && UserRole::isAdmin(auth()->user()->role) &&
          Company::find($companyId)->currency_code == CurrencyCode::USD()->getIndex()
        ) {
            $currencyRate = $entity->currency_rate_company;
            if ($currencyRate === null) {
                $currencyRate = $this->currencyRateEurToUSD;
            } else {
                $currencyRate = safeDivide(1, $currencyRate);
            }
            $attributes['unit_price'] = $attributes['unit_price'] * $currencyRate;
            foreach ($modifiers as &$modifier) {
                if ($modifier['quantity_type'] == 1) {
                    $modifier['quantity'] = $modifier['quantity'] * $currencyRate;
                }
            }
        }

        if ($entity->master && (!array_key_exists('company_id', $attributes) || ($attributes['company_id'] === null))) {
            $serviceId = array_key_exists('service_id', $attributes) && !($attributes['service_id'] === null) ? $attributes['service_id'] : null;
            $attributes['company_id'] = $serviceId ? $this->setCompanyIdIfMaster($serviceId) : getTenantWithConnection();
        }

        $item = $this->itemRepository->createItem($attributes, $modifiers);
        $this->savePricesAndCurrencyRates($companyId, $entityId, $class);

        if (($class == Order::class || $class == Invoice::class) && $entity->master) {
            $this->syncWithShadowItems($entity, $attributes, $modifiers, 'create', $item->id);
        }

        return $item;
    }

    public function updateItem(
        string $companyId,
        string $entityId,
        string $itemId,
        array $attributes,
        string $class
    ): Item {
        $entity = $class::with('priceModifiers')->findOrFail($entityId);
        $modifiers = [];
        if (array_key_exists('price_modifiers', $attributes)) {
            $modifiers = $attributes['price_modifiers'];
            unset($attributes['price_modifiers']);
        }
        if (
          !$entity->manual_input && $class != PurchaseOrder::class && UserRole::isAdmin(auth()->user()->role) &&
          Company::find($companyId)->currency_code == CurrencyCode::USD()->getIndex()
        ) {
            $currencyRate = $entity->currency_rate_company;
            if ($currencyRate === null) {
                $currencyRate = $this->currencyRateEurToUSD;
            } else {
                $currencyRate = safeDivide(1, $currencyRate);
            }
            $attributes['unit_price'] = $attributes['unit_price'] * $currencyRate;
            foreach ($modifiers as &$modifier) {
                if ($modifier['quantity_type'] == 1) {
                    $modifier['quantity'] = $modifier['quantity'] * $currencyRate;
                }
            }
        }

        $item = $this->itemRepository->updateItem($entityId, $itemId, $attributes, $modifiers);
        $this->savePricesAndCurrencyRates($companyId, $entityId, $class);

        if ($entity->master) {
            if ($class == Order::class || $class == Invoice::class) {
                if (!array_key_exists('company_id', $attributes) || ($attributes['company_id'] === null)) {
                    $attributes['company_id'] = $item->company_id;
                }
                $this->syncWithShadowItems($entity, $attributes, $modifiers, 'update', $itemId);
            }
        }

        return $item;
    }

    public function deleteItems(
        string $companyId,
        string $entityId,
        array $itemIds,
        string $class
    ): void {
        if ($class == Order::class || $class == Invoice::class) {
            $entity = $class::findOrFail($entityId);
            if ($entity->master) {
                $this->deleteShadowItems($entity, $itemIds);
            }
        }

        $this->itemRepository->deleteItems($itemIds);
        $this->savePricesAndCurrencyRates($companyId, $entityId, $class);
    }

    public function createPriceModifier(
        string $companyId,
        string $entityId,
        array $attributes,
        string $class
    ): PriceModifier {
        $attributes['entity_id'] = $entityId;
        $attributes['entity_type'] = $class;
        $entity = $class::findOrFail($entityId);
        if ($entity->master && $attributes['quantity_type'] == 1) {
            throw new UnprocessableEntityHttpException(
                'Only a percentage discount is allowed on shared entities.'
            );
        }
        if (
          !$entity->manual_input && $class != PurchaseOrder::class && UserRole::isAdmin(auth()->user()->role) &&
          Company::find($companyId)->currency_code == CurrencyCode::USD()->getIndex()
        ) {
            $currencyRate = $entity->currency_rate_company;
            if ($currencyRate === null) {
                $currencyRate = $this->currencyRateEurToUSD;
            } else {
                $currencyRate = safeDivide(1, $currencyRate);
            }
            if ($attributes['quantity_type'] == 1) {
                $attributes['quantity'] = $attributes['quantity'] * $currencyRate;
            }
        }
        $priceModifier = $this->itemRepository->createPriceModifier($attributes);
        $this->savePricesAndCurrencyRates($companyId, $entityId, $class);

        if ($class == Order::class || $class == Invoice::class) {
            if ($entity->master) {
                $this->syncShadowModifiers($entity, $attributes, 'create');
            }
        }

        return $priceModifier;
    }

    public function updatePriceModifier(
        string $companyId,
        string $entityId,
        string $priceModifierId,
        array $attributes,
        string $class
    ): PriceModifier {
        $entity = $class::with('priceModifiers')->findOrFail($entityId);
        if ($entity->master && $attributes['quantity_type'] == 1) {
            throw new UnprocessableEntityHttpException(
                'Only a percentage discount is allowed on shared entities.'
            );
        }
        if (
          !$entity->manual_input && $class != PurchaseOrder::class && UserRole::isAdmin(auth()->user()->role) &&
          Company::find($companyId)->currency_code == CurrencyCode::USD()->getIndex()
        ) {
            $currencyRate = $entity->currency_rate_company;
            if ($currencyRate === null) {
                $currencyRate = $this->currencyRateEurToUSD;
            } else {
                $currencyRate = safeDivide(1, $currencyRate);
            }
            if ($attributes['quantity_type'] == 1) {
                $attributes['quantity'] = $attributes['quantity'] * $currencyRate;
            }
        }
        $priceModifier = $this->itemRepository->updatePriceModifier($entityId, $priceModifierId, $attributes);
        $this->savePricesAndCurrencyRates($companyId, $entityId, $class);

        if ($class == Order::class || $class == Invoice::class) {
            $entity = $class::with('priceModifiers')->findOrFail($entityId);
            if ($entity->master) {
                $attributes['entity_type'] = $class;
                $this->syncShadowModifiers($entity, $attributes);
            }
        }

        return $priceModifier;
    }

    public function deletePriceModifier(
        string $companyId,
        string $entityId,
        string $priceModifierId,
        string $class
    ): void {
        $this->itemRepository->deletePriceModifier($entityId, $priceModifierId);
        $this->savePricesAndCurrencyRates($companyId, $entityId, $class);

        if ($class == Order::class || $class == Invoice::class) {
            $entity = $class::with('priceModifiers')->findOrFail($entityId);
            if ($entity->master) {
                $attributes['entity_type'] = $class;
                $this->syncShadowModifiers($entity, $attributes);
            }
        }
    }

    public function savePricesAndCurrencyRates(string $companyId, string $entityId, string $class)
    {
        $model = $class::with('items', 'project')->findOrFail($entityId);
        $useResource = false;
        $company = Company::findOrFail($companyId);
        $customerCurrency = CurrencyCode::make($model->currency_code)->__toString();
        $currencyRates = getCurrencyRates();
        $penalty = 1;
        $purchaseOrder = null;

        if ($class == PurchaseOrder::class || ($class == Invoice::class && !($model->purchase_order_id === null))) {
            $useResource = true;
            $resourceService = App::make(ResourceService::class);
            $purchaseOrder = ($class == PurchaseOrder::class) ? $model : $model->purchaseOrder;
            $resource = $resourceService->findBorrowedResource($purchaseOrder->resource_id);
            if (!$resource) {
                $employeeService = App::make(EmployeeService::class);
                $resource = $employeeService->findBorrowedEmployee($purchaseOrder->resource_id);
            }
        }

        if (VatStatus::isAlways($model->vat_status)) {
            $taxIsApplicable = true;
        } elseif (VatStatus::isNever($model->vat_status)) {
            $taxIsApplicable = false;
        } elseif ((!$useResource && $model->project->contact->customer->non_vat_liable) || ($useResource && $resource->non_vat_liable)) {
            $taxIsApplicable = true;
        } else {
            if (!$model->legal_entity_id) {
                $taxIsApplicable = false;
            } else {
                $taxIsApplicable = $useResource ?
                $resource->country == $model->legalEntity->address->country :
                $model->project->contact->customer->billing_address->country == $model->legalEntity->address->country;
            }
        }

        if ($taxIsApplicable) {
            $legalEntityTaxRate = empty($model->vat_percentage) ? getTaxRate($model->date, $model->legal_entity_id) : $model->vat_percentage;
        } else {
            $model->manual_vat = 0;
            $model->total_vat = 0;
            $model->total_vat_usd = 0;
        }

        if ($company->currency_code == CurrencyCode::USD()->getIndex()) {
            if (!$model->currency_rate_company) {
                $model->currency_rate_company = safeDivide(1, $this->currencyRateEurToUSD);
            }
            if (!$model->currency_rate_customer) {
                $model->currency_rate_customer = $model->currency_rate_company * $currencyRates['rates'][$customerCurrency];
            }
        } else {
            if (!$model->currency_rate_company) {
                $model->currency_rate_company = $this->currencyRateEurToUSD;
            }
            if (!$model->currency_rate_customer) {
                $model->currency_rate_customer = $currencyRates['rates'][$customerCurrency];
            }
        }

        if ($class == PurchaseOrder::class) {
            if (!$model->currency_rate_resource) {
                $resourceCurrency = $model->manual_input ? CurrencyCode::make($model->currency_code)->__toString()
                : CurrencyCode::make($resource->default_currency)->__toString();
                $model->currency_rate_resource = $currencyRates['rates'][$resourceCurrency];
            }
            $purchaseOrder->currency_rate_resource = $model->currency_rate_resource;
        }

        if ($model->manual_input || $useResource) {
            $model->manual_price = entityPrice($class, $entityId);
            $model->manual_price = applyPenalty($model->manual_price, $purchaseOrder);
            $model->total_price = $model->manual_price * ($useResource ? safeDivide(1, $purchaseOrder->currency_rate_resource)
            : ($company->currency_code == CurrencyCode::EUR()->getIndex() ?
            safeDivide(1, $model->currency_rate_customer) : $model->currency_rate_company * safeDivide(1, $model->currency_rate_customer)));

            $model->total_price_usd = $model->total_price *
              ($company->currency_code == CurrencyCode::USD()->getIndex() ? safeDivide(1, $model->currency_rate_company) :
                $model->currency_rate_company);

            if ($taxIsApplicable) {
                $model->manual_vat = $model->manual_price * ($legalEntityTaxRate / 100);
                $model->manual_price += $model->manual_vat;
                $model = $this->setTaxesOnPrices($model, $legalEntityTaxRate);
            }
        } else {
            if ($company->currency_code == CurrencyCode::USD()->getIndex()) {
                $model->total_price_usd = entityPrice($class, $entityId);
                $model->total_price = entityPrice($class, $entityId, false, $model->currency_rate_company);
                if ($taxIsApplicable) {
                    $model = $this->setTaxesOnPrices($model, $legalEntityTaxRate);
                }
            } else {
                $model->total_price = entityPrice($class, $entityId);
                $model->total_price_usd = entityPrice($class, $entityId, false, $model->currency_rate_company);
                if ($taxIsApplicable) {
                    $model = $this->setTaxesOnPrices($model, $legalEntityTaxRate);
                }
            }
        }
        $model->save();
    }

    private function setTaxesOnPrices(Model $model, float $taxRate): Model
    {
        $model->total_vat_usd = round($model->total_price_usd * ($taxRate / 100), 2);
        $model->total_price_usd += $model->total_vat_usd;
        $model->total_vat = round($model->total_price * ($taxRate / 100), 2);
        $model->total_price += $model->total_vat;

        return $model;
    }

    private function setCompanyIdIfMaster(string $serviceId): ?string
    {
        $query = [
          'query' => [
              'bool' => [
                  'must' => [
                      'match' => [
                          'id' => $serviceId
                      ]
                  ]
              ]
          ]
        ];

        $service = Service::searchAllTenantsQuery('services', $query);

        if (!empty($service['hits']['hits'])) {
            return $service['hits']['hits'][0]['_source']['company_id'];
        }

        return getTenantWithConnection();
    }

    private function syncWithShadowItems($entity, $attributes, $modifiers, $action, $itemId)
    {
        $company = Company::find(getTenantWithConnection());
        if ($attributes['company_id'] != $company->id) {
            if ($entity instanceof Order) {
                $masterShadow = $entity->shadows->where('shadow_company_id', $attributes['company_id'])->first();
                if (!$masterShadow) {
                    $masterShadow = $this->createShadowOrder($entity, $company->id, $attributes['company_id']);
                }
            } else {
                $masterShadow = $entity->shadows->where('shadow_company_id', $attributes['company_id'])->first();
                if (!$masterShadow) {
                    $masterOrder = $entity->order->shadows->where('shadow_company_id', $attributes['company_id'])->first();
                    $masterShadow = $this->createShadowInvoice($entity, $company->id, $attributes['company_id'], $masterOrder->shadow_id);
                }
            }

            if ($masterShadow) {
                $shadowCompany = Company::find($attributes['company_id']);
                Tenancy::setTenant($shadowCompany);

                if ($entity instanceof Order) {
                    $model = Order::find($masterShadow->shadow_id);
                    $attributes['entity_type'] = Order::class;
                } else {
                    $model = Invoice::find($masterShadow->shadow_id);
                    $attributes['entity_type'] = Invoice::class;
                }

                $attributes['entity_id'] = $model->id;
                if (!$entity->manual_input &&  $company->currency_code != $shadowCompany->currency_code) {
                    if ($shadowCompany->currency_code == CurrencyCode::USD()->getIndex()) {
                        $attributes['unit_price'] = $attributes['unit_price'] * $this->currencyRateEurToUSD;
                    } else {
                        $attributes['unit_price'] = $attributes['unit_price'] * safeDivide(1, $this->currencyRateEurToUSD);
                    }
                }

                if ($action == 'create') {
                    $attributes['master_item_id'] = $itemId;
                    $this->itemRepository->createItem($attributes, $modifiers);
                } else {
                    $item = Item::with('priceModifiers')->where([['master_item_id', $itemId], ['company_id', $shadowCompany->id]])->first();

                    $modifiers = array_map(function ($modifier) use ($item) {
                        $shadowModifier = $item->priceModifiers->where('description', $modifier['description'])->first();
                        if ($shadowModifier) {
                            $modifier['id'] = $shadowModifier->id;
                        } else {
                            unset($modifier['id']);
                        }
                        $modifier['entity_id'] = $item->id;

                        return $modifier;
                    }, $modifiers);

                    $this->itemRepository->updateItem($model->id, $item->id, $attributes, $modifiers);
                }

                $this->savePricesAndCurrencyRates($shadowCompany->id, $model->id, $attributes['entity_type']);

                Tenancy::setTenant($company);
            }
        }
    }

    private function deleteShadowItems($entity, $itemIds)
    {
        $company = Company::find(getTenantWithConnection());
        $items = Item::whereIn('id', $itemIds)->get();
        $itemsArray = $items->groupBy('company_id');

        foreach ($itemsArray as $array) {
            if ($array[0]->company_id == $company->id) {
                continue;
            }

            $order = $entity->shadows->where('shadow_company_id', $array[0]->company_id)->first();

            if ($order) {
                $shadowCompany = Company::find($array[0]->company_id);
                Tenancy::setTenant($shadowCompany);
                $ids = $array->pluck('id');
                $shadowItems = Item::whereIn('master_item_id', $ids)->pluck('id')->toArray();
                $this->itemRepository->deleteItems($shadowItems);

                if ($entity instanceof Order) {
                    $this->savePricesAndCurrencyRates($shadowCompany->id, $order->shadow_id, Order::class);
                } else {
                    $this->savePricesAndCurrencyRates($shadowCompany->id, $order->shadow_id, Invoice::class);
                }
            }
        }
        Tenancy::setTenant($company);
    }

    private function syncShadowModifiers($entity, $attributes, $action = null)
    {
        $company = Company::find(getTenantWithConnection());
        $modifiers = $entity->priceModifiers;
        $shadowOrders = $entity->shadows;
        foreach ($shadowOrders as $shadowOrder) {
            $shadowCompany = Company::find($shadowOrder->shadow_company_id);
            Tenancy::setTenant($shadowCompany);

            if ($entity instanceof Order) {
                $model = Order::with('priceModifiers')->find($shadowOrder->shadow_id);
                ;
            } else {
                $model = Invoice::with('priceModifiers')->find($shadowOrder->shadow_id);
            }

            if ($action == 'create') {
                $attributes['entity_id'] = $model->id;
                $this->itemRepository->createPriceModifier($attributes);
            } else {
                $model->priceModifiers()->delete();
                $modifiers->each(function ($modifier) use ($model) {
                    $newModifier = $modifier->replicate();
                    $newModifier->entity_id = $model->id;
                    $newModifier->save();
                });
            }

            $this->savePricesAndCurrencyRates($shadowCompany->id, $model->id, $attributes['entity_type']);
        }
        Tenancy::setTenant($company);
    }

    private function createShadowOrder($masterOrder, $masterCompanyId, $shadowCompanyId)
    {
        $projectRepository = App::make(ProjectRepositoryInterface::class);
        $project = $masterOrder->project;
        $contact = Contact::find($project->contact_id);
        $shadowCompany = Company::find($shadowCompanyId);
        Tenancy::setTenant($shadowCompany);

        $shadowProject = $projectRepository->createProjectForQuote($project->name, $contact, $project->salesPersons->pluck('id'));
        $shadowOrder = Order::create([
          'project_id' => $shadowProject->id,
          'quote_id' => null,
          'date' => $masterOrder->date,
          'deadline' => $masterOrder->deadline,
          'status' => $masterOrder->status,
          'number' => 'Shadow of ' . $masterOrder->number,
          'reference' => $masterOrder->reference,
          'currency_code' => (int)$masterOrder->currency_code,
          'currency_rate_company' => null,
          'currency_rate_customer' => null,
          'total_price' => 0,
          'total_vat' => 0,
          'total_price_usd' => 0,
          'total_vat_usd' => 0,
          'manual_input' => $masterOrder->manual_input,
          'manual_price' => 0,
          'manual_vat' => 0,
          'legal_entity_id' => $masterOrder->legal_entity_id,
          'shadow' => true,
          'vat_status' => $masterOrder->vat_status,
          'vat_percentage' => $masterOrder->vat_percentage,
        ]);

        if (!empty($masterOrder->priceModifiers)) {
            $modifiers = $masterOrder->priceModifiers;
            $modifiers->each(function ($item) use ($shadowOrder) {
                $quoteModifier = $item->replicate();
                $quoteModifier->entity_id = $shadowOrder->id;
                $quoteModifier->entity_type = Order::class;
                $quoteModifier->save();
            });
        }

        return MasterShadow::create([
          'master_id' => $masterOrder->id,
          'shadow_id' => $shadowOrder->id,
          'master_company_id' => $masterCompanyId,
          'shadow_company_id' => $shadowCompanyId,
        ]);
    }

    private function createShadowInvoice($masterInvoice, $masterCompanyId, $shadowCompanyId, $shadowOrderId)
    {
        $shadowCompany = Company::find($shadowCompanyId);
        Tenancy::setTenant($shadowCompany);

        $order = Order::find($shadowOrderId);

        $isIntraCompany = isIntraCompany($order->project->contact->customer->id ?? null);

        $shadowInvoice = Invoice::create([
          'created_by' => $masterInvoice->created_by,
          'project_id' => $order->project_id,
          'order_id' => $order->id,
          'type' => InvoiceType::accrec()->getIndex(),
          'date' => $masterInvoice->date,
          'due_date' => $masterInvoice->due_date,
          'status' => InvoiceStatus::draft()->getIndex(),
          'number' => $masterInvoice->number,
          'reference' => $masterInvoice->reference,
          'currency_code' => (int)$masterInvoice->currency_code,
          'currency_rate_company' => $masterInvoice->currency_rate_company,
          'currency_rate_customer' => $masterInvoice->currency_rate_customer,
          'total_price' => 0,
          'total_vat' => 0,
          'total_price_usd' => 0,
          'total_vat_usd' => 0,
          'manual_input' => $masterInvoice->manual_input,
          'manual_price' => 0,
          'manual_vat' => 0,
          'legal_entity_id' => $masterInvoice->legal_entity_id,
          'master' => false,
          'shadow' => true,
          'vat_status' => $masterInvoice->vat_status,
          'vat_percentage' => $masterInvoice->vat_percentage,
          'eligible_for_earnout'=> !$isIntraCompany,
        ]);

        if (!empty($masterInvoice->priceModifiers)) {
            $modifiers = $masterInvoice->priceModifiers;
            $modifiers->each(function ($item) use ($shadowInvoice) {
                $quoteModifier = $item->replicate();
                $quoteModifier->entity_id = $shadowInvoice->id;
                $quoteModifier->entity_type = Invoice::class;
                $quoteModifier->save();
            });
        }

        return MasterShadow::create([
          'master_id' => $masterInvoice->id,
          'shadow_id' => $shadowInvoice->id,
          'master_company_id' => $masterCompanyId,
          'shadow_company_id' => $shadowCompanyId,
        ]);
    }

    /**
     * When we share a command that already has items, we assign the items to it
     * @param string $companyId
     * @param Order $order
     * @return void
     */
    public function assignItemsToTheCompany(string $companyId, Order $order): void
    {
        if ($order->master && !$order->shadow) {
            $order->items()->whereNull('company_id')->update(['company_id' => $companyId]);
        }
    }
}
