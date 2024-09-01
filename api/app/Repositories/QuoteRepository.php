<?php


namespace App\Repositories;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Contracts\Repositories\QuoteRepositoryInterface;
use App\Enums\CustomerStatus;
use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Contact;
use App\Enums\QuoteStatus;
use App\Models\Item;
use App\Models\MasterShadow;
use App\Models\Order;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\ItemService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Tenancy\Facades\Tenancy;

/**
 * Class QuoteRepository
 *
 * @deprecated
 */
class QuoteRepository
{
    protected Quote $quote;
    protected CommissionService $commissionService;

    public function __construct(
        Quote $quote
    ) {
        $this->quote = $quote;
        $this->commissionService = app(CommissionService::class);
    }

    public function createForProject($project_id, $attributes)
    {
        $attributes['project_id'] = $project_id;
        if (!array_key_exists('status', $attributes)) {
            $attributes['status'] = QuoteStatus::draft()->getIndex();
        }
        $format = Setting::first();
        $attributes['number'] = transformFormat($format->quote_number_format, $format->quote_number + 1);
        $quote = $this->quote->create($attributes);
        $format->quote_number += 1;
        $format->save();
        if (array_key_exists('contact_id', $attributes) && $quote->project->contact_id != $attributes['contact_id']) {
            $quote->project->contact_id = $attributes['contact_id'];
            $quote->project->save();
        }
        return $quote;
    }

    public function create($attributes)
    {
        if (!$contact = Contact::find($attributes['contact_id'])) {
            throw new ModelNotFoundException();
        } elseif ($contact->customer === null) {
            throw new ModelNotFoundException();
        }

        $project = Project::create([
            'name' => $attributes['name'] ?? generateProjectUniqueName($contact->id),
            'contact_id' => $contact->id,
        ]);

        $project->salesPersons()->attach($attributes['sales_person_id']);

        $format = Setting::first();
        $attributes['project_id'] = $project->id;
        $attributes['number'] = transformFormat($format->quote_number_format, $format->quote_number + 1);
        if (!array_key_exists('status', $attributes)) {
            $attributes['status'] = QuoteStatus::draft()->getIndex();
        }
        $quote = $this->quote->create($attributes);
        $format->quote_number += 1;
        $format->save();

        return $quote;
    }

    public function update(Quote $quote, $attributes, $format)
    {
        $oldDate = $quote->date;
        if (key_exists('contact_id', $attributes)) {
            if (!$contact = Contact::find($attributes['contact_id'])) {
                throw new ModelNotFoundException();
            } elseif ($contact->customer === null) {
                throw new ModelNotFoundException();
            }

            $quote->project->contact_id = $attributes['contact_id'];
        }

        if (key_exists('sales_person_id', $attributes)) {
            if ($attributes['sales_person_id']) {
                foreach ($attributes['sales_person_id'] as $salesPersonId) {
                    // Check if the sales person is already attached
                    $salesPerson = User::find($salesPersonId);
                    if (!$salesPerson) {
                        continue;
                    }
                      // Update or insert the record in the 'project_sales_persons' table
                    if ($salesPerson && !isSalesPersonAttached($salesPersonId, $quote->project->id)) {
                        $salesPerson->saleProjects()->attach($quote->project);
                    }
                    if ($salesPerson && !isSalesPersonAttachedToQuote($quote->id, $salesPersonId)) {
                        $salesPerson->quotes()->attach($quote);
                    }
                }
            }
        }

        if (key_exists('second_sales_person_id', $attributes)) {
            if ($attributes['second_sales_person_id']) {
                foreach ($attributes['second_sales_person_id'] as $salesPersonId) {
                    // Check if the lead gen is already attached
                    $salesPerson = User::find($salesPersonId);
                    if (!$salesPerson) {
                        continue;
                    }
                    // Update or insert the record in the 'project_sales_persons' table
                    if ($salesPerson && !isLeadGenAttached($salesPersonId, $quote->project->id)) {
                        $salesPerson->leadGenProjects()->attach($quote->project);
                    }
                }
            }
        }

        if (key_exists('name', $attributes)) {
            $quote->project->update(['name'=>$attributes['name']]);
        }

        if (key_exists('name', $attributes)) {
            $quote->project->update(['name'=>$attributes['name']]);
        }

        if (key_exists('status', $attributes)) {
            $nextStatus = $attributes['status'];
            if (!QuoteStatus::isDeclined($nextStatus)) {
                unset($attributes['reason_of_refusal']);
            }
            if (QuoteStatus::isOrdered($nextStatus)) {
                $this->createOrder($quote->project_id, $quote, $attributes['deadline'], $format);
            }
        }

        $quote->project->save();
        $quote->update($attributes);

        if (key_exists('date', $attributes) && !(strtotime($attributes['date']) == strtotime($oldDate))) {
            $itemService = App::make(ItemService::class);
            $itemService->savePricesAndCurrencyRates(getTenantWithConnection(), $quote->id, Quote::class);
        }

        return $quote->refresh();
    }

    public function createOrder($project_id, $quote, $deadline, $format, $isShadow = false, $masterOrder = null)
    {
        $itemService = App::make(ItemService::class);
        $company = Company::find(getTenantWithConnection());

        if ($newOrder = $quote->project->order()->doesntExist()) {
            $order = Order::create([
            'project_id' => $project_id,
            'quote_id' => $isShadow ? null : $quote->id,
            'date' => now(),
            'deadline' => $deadline,
            'status' => OrderStatus::draft()->getIndex(),
            'number' => $isShadow ? 'Shadow of ' . $format : transformFormat($format->order_number_format, $format->order_number + 1),
            'reference' => $quote->reference,
            'currency_code' => (int)$quote->currency_code,
            'currency_rate_company' => $quote->currency_rate_company,
            'currency_rate_customer' => $quote->currency_rate_customer,
            'total_price' => 0,
            'total_vat' => 0,
            'total_price_usd' => 0,
            'total_vat_usd' => 0,
            'manual_input' => $quote->manual_input,
            'manual_price' => 0,
            'manual_vat' => 0,
            'legal_entity_id' => $quote->legal_entity_id,
            'master' => (bool)$quote->master,
            'shadow' => (bool)$quote->shadow,
            'vat_status' => $quote->vat_status,
            'vat_percentage' => $quote->vat_percentage,
            ]);
        } else {
            $order = $quote->project->order;
        }

        if (!empty($quote->items)) {
            $quote->items->each(function ($item) use ($order, $isShadow, $masterOrder, $company) {
                if ($isShadow) {
                    if ($order->manual_input) {
                        $masterId = $masterOrder->items->where('service_name', $item->service_name)
                        ->where('company_id', $company->id)->first()->id;
                    } else {
                        $masterId = $masterOrder->items->where('service_id', $item->service_id)->first()->id;
                    }
                }
                $orderItem = $item->replicate();
                $orderItem->entity_id = $order->id;
                $orderItem->entity_type = Order::class;
                $orderItem->master_item_id = $isShadow ? $masterId : null;
                $orderItem->save();
                if (!empty($item->priceModifiers)) {
                    $item->priceModifiers->each(function ($modifier) use ($orderItem) {
                        $orderModifier = $modifier->replicate();
                        $orderModifier->entity_id = $orderItem->id;
                        $orderModifier->entity_type = Item::class;
                        $orderModifier->save();
                    });
                }
            });
        }

        if (!empty($quote->priceModifiers)) {
            $modifiers = $quote->priceModifiers;
            if (!$newOrder && $order->quote_id) {
                if ($order->priceModifiers->where('description', 'Project Management')->count()) {
                    $modifiers = $modifiers->filter(function ($item) {
                        return $item->description != 'Project Management';
                    });
                }
            }
            $modifiers->each(function ($item) use ($order) {
                $orderModifier = $item->replicate();
                $orderModifier->entity_id = $order->id;
                $orderModifier->entity_type = Order::class;
                $orderModifier->save();
            });
        }

        if ($order->project->contact->customer->status != CustomerStatus::active()->getIndex()) {
            $order->project->contact->customer->status = CustomerStatus::active()->getIndex();
            $order->project->contact->customer->save();
        }
        if (!$newOrder) {
            $order->quote_id = $order->quote_id ?? $quote->id;
            $order->save();
        }
        $itemService->savePricesAndCurrencyRates($company->id, $order->id, Order::class);

        if ($quote->master) {
            if (!empty($quote->items)) {
                $this->createShadowEntity($quote, $company, $order->load('items'), $itemService);
                Tenancy::setTenant($company);
            }
        }

        return $order;
    }

    private function convertUnitPrice($price, $rate, $masterCurrency, $shadowCurrency, $manualInput)
    {
        if (!$manualInput && $masterCurrency != $shadowCurrency) {
            return $price * $rate;
        }

        return $price;
    }

    public function createShadowEntity(Quote $quote, Company $company, Order $order, ItemService $itemService)
    {
        $quoteRepository = App::make(QuoteRepositoryInterface::class);
        $projectRepository = App::make(ProjectRepositoryInterface::class);
        $project = $quote->project;
        $contact = Contact::find($project->contact_id);

        if ($contact) {
            $itemsArray = $quote->items->groupBy('company_id')->sortBy('order');
            foreach ($itemsArray as $array) {
                if ($array[0]->company_id == $company->id) {
                    continue;
                }
                $shadowCompany = Company::find($array[0]->company_id);
                $itemOrder = 0;
                Tenancy::setTenant($shadowCompany);

                $shadowProject = $projectRepository->createProjectForQuote($project->name, $contact, $project->salesPersons->pluck('id')->toArray());

                $shadowQuote = $quoteRepository->create([
                'project_id' => $shadowProject->id,
                'number' => $quote->number,
                'date' => $quote->date->toDateString(),
                'expiry_date' => $quote->expiry_date->toDateString(),
                'reference' => $quote->reference,
                'currency_code' => $quote->currency_code,
                'manual_input' => $quote->manual_input,
                'down_payment' => $quote->down_payment,
                'legal_entity_id' => $quote->legal_entity_id,
                'shadow' => true,
                'status' => QuoteStatus::ordered()->getIndex(),
                'vat_status' => $quote->vat_status,
                'vat_percentage' => $quote->vat_percentage,
                ]);

                $array->each(function ($item) use ($shadowQuote, &$itemOrder, $quote, $company, $shadowCompany) {
                    $quoteItem = $item->replicate();
                    $quoteItem->entity_id = $shadowQuote->id;
                    $quoteItem->entity_type = Quote::class;
                    $quoteItem->order = $itemOrder;
                    $quoteItem->unit_price = $this->convertUnitPrice(
                        $item->unit_price,
                        $quote->currency_rate_company,
                        $company->currency_code,
                        $shadowCompany->currency_code,
                        $quote->manual_input
                    );
                    $quoteItem->master_item_id = $item->id;
                    $quoteItem->save();
                    if (!empty($item->priceModifiers)) {
                        $item->priceModifiers->each(function ($modifier) use ($quoteItem) {
                                $quoteModifier = $modifier->replicate();
                                $quoteModifier->entity_id = $quoteItem->id;
                                $quoteModifier->entity_type = Item::class;
                                $quoteModifier->save();
                        });
                    }
                    $itemOrder += 1;
                });

                if (!empty($quote->priceModifiers)) {
                      $modifiers = $quote->priceModifiers;
                      $modifiers->each(function ($item) use ($shadowQuote) {
                        $quoteModifier = $item->replicate();
                        $quoteModifier->entity_id = $shadowQuote->id;
                        $quoteModifier->entity_type = Quote::class;
                        $quoteModifier->save();
                      });
                }

                $itemService->savePricesAndCurrencyRates($shadowCompany->id, $shadowQuote->id, Quote::class);

                $shadowOrder = $this->createOrder(
                    $shadowQuote->project_id,
                    $shadowQuote,
                    $order->deadline,
                    $order->number,
                    true,
                    $order
                );

                MasterShadow::create([
                  'master_id' => $order->id,
                  'shadow_id' => $shadowOrder->id,
                  'master_company_id' => $company->id,
                  'shadow_company_id' => $shadowCompany->id,
                ]);
            }
        }
    }
}
