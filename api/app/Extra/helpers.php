<?php

use App\Contracts\Repositories\GlobalTaxRateRepositoryInterface;
use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Enums\CommissionModel;
use App\Enums\CommissionPercentageType;
use App\Enums\CurrencyCode;
use App\Enums\EntityModifierDescription;
use App\Enums\EntityPenaltyType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OrderStatus;
use App\Enums\PriceModifierQuantityType;
use App\Enums\PriceModifierType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Jobs\ElasticUpdateAssignment;
use App\Models\Commission;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\GlobalTaxRate;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Project;
use App\Models\ProjectEmployee;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\SalesCommissionPercentage;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\PurchaseOrderService;
use App\Services\QuoteService;
use App\Traits\Models\AutoElastic;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use Tenancy\Facades\Tenancy;

if (!function_exists('getHashKey')) {
    function getHashKey()
    {
        $key = config('app.key');

        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return $key;
    }
}

if (!function_exists('createNewToken')) {
    function createNewToken($hashKey)
    {
        return hash_hmac('sha256', Str::random(40), $hashKey);
    }
}

if (!function_exists('indices')) {
    function indices()
    {
        return modelsUsing(AutoElastic::class);
    }
}

if (!function_exists('modelsUsing')) {
    function modelsUsing(string $trait)
    {
        if (!isset($uses)) {
            static $uses = [];
            $models = [];
            $finder = new Finder();
            $finder->files()->in(app_path('Models'));
            foreach ($finder as $file) {
                if (basename($file->getPath()) == 'Models') {
                    $models[] = basename($file, '.php');
                }
            }
            foreach ($models as $model) {
                $uses[$model] = class_uses_recursive('App\Models\\' . $model);
            }
        }
        $results = [];
        foreach ($uses as $class => $traits) {
            if (array_key_exists($trait, $traits)) {
                $results[] = 'App\Models\\' . $class;
            }
        }

        return $results;
    }
}

if (!function_exists('transformFormat')) {
    /**
     * Fills string with a dates according to the given template
     *
     * @param  string $templateStringToProcess
     * @param  string $number
     *
     * @return string
     */
    function transformFormat(string $templateStringToProcess, string $number)
    {
        $templateStringToProcess = Str::upper($templateStringToProcess);
        // let's collect entries in square brackets, that means we have to process this as a date
        $dateTemplateToFillWithRealDate = [];
        preg_match_all('/\[[^\]]*\]/', $templateStringToProcess, $dateTemplateToFillWithRealDate);
        // remove square brackets, for now we don't need them here
        $dateTemplateToFillWithRealDate = collect($dateTemplateToFillWithRealDate[0])->map(function ($entry) {
            return ['template' => substr($entry, 1, -1)];
        })->toArray();
        $numberLength = Str::length($number);

        // we'll put date and will save the template which was used during the search to use it in the replacement later
        foreach ($dateTemplateToFillWithRealDate as &$entry) {
            if ($Y = substr_count(Arr::get($entry, 'template'), 'Y')) {
                $val = date('Y');
                if ($Y >= 4) {
                    $val = date('Y');
                } elseif ($Y < 4 && $Y > 0) {
                    $val = date('y');
                }
            } elseif ($M = substr_count(Arr::get($entry, 'template'), 'M')) {
                $val = date('m');
                if ($M >= 2) {
                    $val = date('m');
                } elseif ($M < 2 && $M > 0) {
                    $val = date('n');
                }
            } elseif ($M = substr_count(Arr::get($entry, 'template'), 'Q')) {
                $val = (string) ceil(date('m', time()) / 3);
            }
            Arr::set($entry, 'binding', $val);
        }

        // process the number part, to
        // convert XXXX to 0001 for example if the $number equals to 1
        preg_match_all('/X+/', $templateStringToProcess, $matches, PREG_OFFSET_CAPTURE);
        $lastMatch = $matches[0][count($matches[0]) - 1];

        $lastMatchLength = strlen($lastMatch[0]);
        $numberPadding = $numberLength < $lastMatchLength ?
          $lastMatchLength - $numberLength :
          0;
        $templateStringToProcess = substr_replace(
            $templateStringToProcess,
            str_repeat('0', $numberPadding) . $number,
            $lastMatch[1],
            $lastMatchLength
        );

        // go through the date templates entries and process
        // them by replacing the source string pieces with actual bindings
        collect($dateTemplateToFillWithRealDate)->map(function ($pair) use (&$templateStringToProcess) {
            $searchFor = '[' . Arr::get($pair, 'template') . ']';
            $replaceWith = Arr::get($pair, 'binding');
            $templateStringToProcess = str_replace($searchFor, $replaceWith, $templateStringToProcess);
        });

        return $templateStringToProcess;
    }
}


if (!function_exists('orderPriceModifiers')) {
    function orderPriceModifiers($entity, $modifiers)
    {
        if (!empty($entity->project->price_modifiers_calculation_logic)) {
            return orderPriceModifierNewLogic($modifiers);
        } else {
            return orderPriceModifierOldLogic($modifiers);
        }
    }
}

if (!function_exists('getPriceModifiers')) {
    function getPriceModifiers($entity)
    {
        if (!empty($entity->project->price_modifiers_calculation_logic)) {
            return $entity->priceModifiers->filter(function ($modifier) {
                return $modifier->description != EntityModifierDescription::transaction_fee()->getValue();
            });
        } else {
            return $entity->priceModifiers;
        }
    }
}

if (!function_exists('orderPriceModifierOldLogic')) {
    function orderPriceModifierOldLogic($modifiers)
    {
        return $modifiers;
    }
}

if (!function_exists('orderPriceModifierNewLogic')) {
    function orderPriceModifierNewLogic($modifiers)
    {
        $modifierOrders = [
          EntityModifierDescription::project_management()->getValue(),
          EntityModifierDescription::director_fee()->getValue(),
          EntityModifierDescription::special_discount()->getValue(),
          EntityModifierDescription::transaction_fee()->getValue(),
        ];

        $sortedModifiers = $modifiers->sort(function ($a, $b) use ($modifierOrders, $modifiers) {
            $indexA = array_search($a->description, $modifierOrders);
            $indexB = array_search($b->description, $modifierOrders);

            if ($indexA !== $indexB) {
                return $indexA - $indexB;
            }

            $createdAtA = strtotime($a->created_at);
            $createdAtB = strtotime($b->created_at);

            if ($createdAtA !== $createdAtB) {
                return $createdAtA - $createdAtB;
            }

            $positionA = $modifiers->search(function ($item) use ($a) {
                return $item->id === $a->id;
            });

            $positionB = $modifiers->search(function ($item) use ($b) {
                return $item->id === $b->id;
            });

            return $positionA - $positionB;
        });

        return $sortedModifiers;
    }
}


if (!function_exists('itemPrice')) {
    function itemPrice($item, bool $noMod = false, $currencyRate = 1, $withTransactionFees = true)
    {
        $basePrice = round($item->unit_price * $currencyRate, 2) * $item->quantity;

        if ($noMod) {
            return $basePrice;
        }
        if (count($item->priceModifiers) > 0) {
            $priceIncludingPriceModifiers = $item->exclude_from_price_modifiers ? 0 : $basePrice;
            if (!empty($item->entity->project->price_modifiers_calculation_logic)) {
                $basePrice = priceWithModifiersNewLogic($basePrice, $priceIncludingPriceModifiers, $item->priceModifiers, $currencyRate, $withTransactionFees);
            } else {
                $basePrice = priceWithModifiers($basePrice, $item->priceModifiers, $currencyRate, true);
            }
        }

        return $basePrice;
    }
}

if (!function_exists('get_total_price')) {
    function get_total_price(string $class, string $id)
    {


        $entity = $class::with('items', 'items.priceModifiers', 'priceModifiers')->findOrFail($id);

        // TODO: this is an empty statement?
        if ($entity->manual_input) {
            return (float) $entity->manual_price;
        }

        $companyCurrency = Company::find(getTenantWithConnection())->currency_code;
        if ($entity->currency_code != $companyCurrency) {
            $totalWithMods = entityPrice($class, $id, false, $entity->currency_rate_customer, true);
        } else {
            $currencyRate = 1;
            if ($companyCurrency == CurrencyCode::USD()->getIndex()) {
                $currencyRate = safeDivide(1, $entity->currency_rate_customer);
            }
            $totalWithMods = entityPrice($class, $id, false, $currencyRate, true);
        }

        if ($entity->total_vat == 0) {
            $vat = 0;
            $taxRate = 0;
        } else {
            $taxRate = empty($entity->vat_percentage) ? getTaxRate($entity->date, $entity->legal_entity_id) : $entity->vat_percentage;
            $vat = round($totalWithMods * ($taxRate / 100), 2);
        }

        return (float) $totalWithMods + $vat;
    }
}



if (!function_exists('entity_price_affected_by_modifiers')) {
    function entity_price_affected_by_modifiers(string $class, string $id, $currencyRate = 1)
    {
        $entity = $class::with('items', 'items.priceModifiers', 'priceModifiers')->findOrFail($id);
        $priceBeforeModifiers = 0;
        if (count($entity->items) > 0) {
            foreach ($entity->items as $item) {
                if (empty($item->exclude_from_price_modifiers)) {
                    $priceBeforeModifiers += itemPrice($item, false, $currencyRate);
                }
            }
        }

        return $priceBeforeModifiers;
    }
}

if (!function_exists('entityPrice')) {
    function entityPrice(string $class, string $id, bool $noMod = false, $currencyRate = 1, $withTransactionFees = true)
    {
        $entity = $class::with('items', 'items.priceModifiers', 'priceModifiers')->findOrFail($id);
        $priceBeforeModifiers = 0;
        $priceIncludingPriceModifiers = 0;
        if (count($entity->items) > 0) {
            foreach ($entity->items as $item) {
                $price = itemPrice($item, false, $currencyRate);
                if (empty($item->exclude_from_price_modifiers)) {
                    $priceIncludingPriceModifiers += $price;
                }
                $priceBeforeModifiers += $price;
            }
            if ($noMod) {
                return $priceBeforeModifiers;
            }

            if (count($entity->priceModifiers) > 0) {
                if (!empty($entity->project->price_modifiers_calculation_logic)) {
                    $priceBeforeModifiers = priceWithModifiersNewLogic($priceBeforeModifiers, $priceIncludingPriceModifiers, $entity->priceModifiers, $currencyRate, $withTransactionFees);
                } else {
                    $priceBeforeModifiers = priceWithModifiers($priceBeforeModifiers, $entity->priceModifiers, $currencyRate, true);
                }
            }
        }

        return $priceBeforeModifiers;
    }
}

if (!function_exists('priceWithModifiers')) {
    function priceWithModifiers(float $price, $modifiers, $currencyRate, $withTransactionFees = true)
    {
        $percentages = $modifiers->where('quantity_type', PriceModifierQuantityType::percentage()->getIndex());
        $fixedModifiers = $modifiers->where('quantity_type', PriceModifierQuantityType::fixed()->getIndex());

        $totalPercentage = 100;
        foreach ($percentages as $percentage) {
            if (!$withTransactionFees && $percentage->description === EntityModifierDescription::transaction_fee()->getValue()) {
                continue;
            }
            $totalPercentage = $percentage->type == PriceModifierType::discount()->getIndex() ? $totalPercentage - $percentage->quantity : $totalPercentage + $percentage->quantity;
        }

        $fixedAmount = 0;
        foreach ($fixedModifiers as $fixed) {
            $fixedAmount = $fixed->type == PriceModifierType::discount()->getIndex() ?
            $fixedAmount - ($fixed->quantity * $currencyRate) : $fixedAmount + ($fixed->quantity * $currencyRate);
        }

        $price = $price * ($totalPercentage / 100) + $fixedAmount;
        return $price;
    }
}

if (!function_exists('priceWithModifiersNewLogic')) {
    function priceWithModifiersNewLogic(float $subtotal, $subtotalIncludingPriceModifiers, $modifiers, $currencyRate, $withTransactionFees = true)
    {
        $subtotalModified = $subtotalIncludingPriceModifiers;
        $modifiers = orderPriceModifierNewLogic($modifiers);
        if (!empty($modifiers)) {
            $subtotalExcludingPriceModifiers = $subtotal - $subtotalModified;

            // apply fixed percentage amount
            $percentageModifiers = $modifiers->where('quantity_type', PriceModifierQuantityType::percentage()->getIndex())
            ->filter(function ($modifier) {
                return !in_array($modifier->description, [EntityModifierDescription::special_discount()->getValue(), EntityModifierDescription::transaction_fee()->getValue()]);
            });
            $totalPercentage = 0;
            foreach ($percentageModifiers as $modifier) {
                  $totalPercentage += $modifier->quantity;
            }
            $percentageAmount = $totalPercentage * $subtotalModified / 100;

            // Apply fixed amount
            $fixedAmountModifiers = $modifiers->where('quantity_type', PriceModifierQuantityType::fixed()->getIndex())
            ->filter(function ($modifier) {
                return !in_array($modifier->description, [EntityModifierDescription::special_discount()->getValue(), EntityModifierDescription::transaction_fee()->getValue()]);
            });
            $fixedAmount = 0;
            foreach ($fixedAmountModifiers as $modifier) {
                  $amount = $modifier->quantity;
                if ($modifier->type == PriceModifierType::discount()->getIndex()) {
                    $fixedAmount += -$amount;
                } else {
                    $fixedAmount += $amount;
                }
            }

            $subtotalModified += $percentageAmount + $fixedAmount * $currencyRate;

            // Apply discrount

            $discountModifiers = $modifiers->where('description', EntityModifierDescription::special_discount()->getValue());
            foreach ($discountModifiers as $modifier) {
                $quantity = $modifier->quantity;
                $quantity = $modifier->type === PriceModifierType::discount()->getIndex() ? -$quantity : $quantity;
                if ($modifier->quantity_type === PriceModifierQuantityType::percentage()->getIndex()) {
                    $subtotalModified += $quantity * $subtotalModified / 100;
                } elseif ($modifier->quantity_type === PriceModifierQuantityType::fixed()->getIndex()) {
                    $subtotalModified += $quantity * $currencyRate;
                }
            }

          //Apply transaction fees
            $subtotalModified += $subtotalExcludingPriceModifiers;
            if ($withTransactionFees) {
                $transactionFeesModifiers = $modifiers->where('description', EntityModifierDescription::transaction_fee()->getValue())
                ->where('quantity_type', PriceModifierQuantityType::percentage()->getIndex());
                $transactionFeeTotalPercentage = 0;
                foreach ($transactionFeesModifiers as $modifier) {
                    $transactionFeeTotalPercentage += $modifier->quantity;
                }
                $subtotalModified += $subtotalModified * $transactionFeeTotalPercentage / 100;
            }
        }
        return $subtotalModified;
    }
}


if (!function_exists('ceiling')) {
    function ceiling($value, $precision = 0)
    {
        $offset = 0.5;
        if ($precision !== 0) {
            $offset /= pow(10, $precision);
        }
        $final = round($value + $offset, $precision, PHP_ROUND_HALF_DOWN);

        return ($final == -0 ? 0 : $final);
    }
}

if (!function_exists('getTenantWithConnection')) {
    function getTenantWithConnection()
    {
        $connection = Tenancy::getTenantConnectionName();
        $id = DB::connection($connection)->getDatabaseName();
        $id = substr_replace($id, '-', 8, 0);
        $id = substr_replace($id, '-', 13, 0);
        $id = substr_replace($id, '-', 18, 0);
        $id = substr_replace($id, '-', 23, 0);
        return $id;
    }
}

if (!function_exists('getTenantConnectionName')) {
    function getTenantConnectionName()
    {
        $connection = Tenancy::getTenantConnectionName();
        $connection_id = DB::connection($connection)->getDatabaseName();
        return $connection_id;
    }
}

if (!function_exists('flooring')) {
    function flooring($value, $precision = 0)
    {
        $p = pow(10, $precision);
        return safeDivide(ceil($value * $p), $p);
    }
}


if (!function_exists('convertXeroStringToDate')) {
    function convertXeroStringToDate($data)
    {
        // convert Microsfot .NET JSON Date format into native PHP DateTime()
        $match = preg_match('/([\d]{11})/', $data, $date);
        if ($match) {
            $seconds = $date[1] / 1000;
        }
        $match = preg_match('/([\d]{12})/', $data, $date);
        if ($match) {
            $seconds = $date[1] / 1000;
        }
        $match = preg_match('/([\d]{13})/', $data, $date);
        if ($match) {
            $seconds = $date[1] / 1000;
        }

        $dateString = date('d-m-Y', $seconds);
        $dateFormat = new \DateTime($dateString);
        return $dateFormat;
    }
}

if (!function_exists('calculateEmployeeCosts')) {
    function calculateEmployeeCosts($company_id, $employee, $project, $hours, $rateEurToUsd, $employeeRate, $month = null)
    {
        if (ProjectEmployee::where('project_id', $project->id)->count() == 0) {
            $project->employee_costs = 0;
            $project->employee_costs_usd = 0;
            $project->external_employee_costs = 0;
            $project->external_employee_costs_usd = 0;
        } else {
            $workingHourRate = getWorkingHoursRate($employee, $month);
            $project->employee_costs += $workingHourRate * $hours * safeDivide(1, $employeeRate);
            $project->employee_costs_usd += $workingHourRate * $hours * safeDivide(1, $employeeRate) * $rateEurToUsd;
            if ($employee->can_be_borrowed && isset($employee->company_id) && $employee->company_id != getTenantWithConnection()) {
                $project->external_employee_costs += $workingHourRate * $hours * safeDivide(1, $employeeRate);
                $project->external_employee_costs_usd += $workingHourRate * $hours * safeDivide(1, $employeeRate) * $rateEurToUsd;
            }
        }

        $project->save();
    }
}

if (!function_exists('getWorkingHoursRate')) {
    function getWorkingHoursRate(Employee $employee, $start_month = null)
    {
        if (empty($employee->working_hours)) {
            return 0;
        }

        if (empty($start_month)) {
            return safeDivide($employee->salary, $employee->working_hours);
        }

        $start_month_timestamp = strtotime($start_month);
        $end_month = date('Y-m-t', $start_month_timestamp);

        $history = $employee->histories()->where(function ($query) use ($start_month, $end_month) {
            $query->where(function ($query) use ($start_month, $end_month) {
                $query->where(function ($query) use ($start_month, $end_month) {
                    $query->whereDate('start_date', '<=', $end_month)
                      ->whereDate('end_date', '>=', $start_month)
                      ->whereNotNull('end_date');
                })->orWhere(function ($query) use ($start_month, $end_month) {
                    $query->whereDate('start_date', '>=', $start_month)
                      ->whereDate('start_date', '<=', $end_month)
                      ->whereNull('end_date');
                })->orWhere(function ($query) use ($start_month) {
                    $query->whereDate('start_date', '<=', $start_month)
                      ->whereNull('end_date');
                });
            });
        })
          ->orderByRaw("CASE WHEN end_date IS NOT NULL THEN DATEDIFF(LEAST(end_date,date('$end_month')), GREATEST(start_date, date('$start_month'))) ELSE DATEDIFF(date('$end_month'), GREATEST(start_date, date('$start_month'))) END DESC")
          ->first();

        if (empty($history)) {
            return safeDivide($employee->salary, $employee->working_hours);
        }

        return safeDivide($history->salary, $history->working_hours) * $history->currency_rate_employee;
    }
}


if (!function_exists('decFormat')) {
    function decFormat($number, int $decimalPoints = 2): string
    {
        return number_format((float) $number, $decimalPoints);
    }
}


if (!function_exists('getTaxRate')) {
    function getTaxRate(\Illuminate\Support\Carbon $date, ?string $legalEntityId = null): float
    {
        $globalTaxRateRepository = App::make(GlobalTaxRateRepositoryInterface::class);

        $taxRate = $globalTaxRateRepository->getTaxRateForPeriod($legalEntityId, $date);

        return $taxRate ? $taxRate->tax_rate : 0;
    }
}

if (!function_exists('convertLoanAmountToEuro')) {
    function convertLoanAmountToEuro(string $companyId, float $amount): float
    {
        if (Company::findOrFail($companyId)->currency_code == CurrencyCode::USD()->getIndex()) {
            $convertedAmount = ceiling($amount * safeDivide(1, Cache::store('file')->get('rates')['rates']['USD']), 2);
        } else {
            $convertedAmount = $amount;
        }

        return $convertedAmount;
    }
}

if (!function_exists('getDocumentMimeType')) {
    function getDocumentMimeType($docData): ?string
    {
        $imagemimetypes = array(
          'pdf'  => 'application/pdf',
          'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'doc'  => 'application/msword',
        );

        $docMimeType = explode(';', $docData)[0];
        $docMimeType = explode(':', $docMimeType)[1];

        foreach ($imagemimetypes as $mime => $type) {
            if ($docMimeType == $type) {
                return $mime;
            }
        }

        return null;
    }
}

if (!function_exists('getPurchaseOrders')) {
    function getPurchaseOrders(string $resourceId): array
    {
        $purchaseOrderService = App::make(PurchaseOrderService::class);
        return $purchaseOrderService->getPurchaseOrdersForExternalResource($resourceId);
    }
}

if (!function_exists('getBeginningOfQuarter')) {
    function getBeginningOfQuarter($date)
    {
        $month = $date->format('n');
        if ($month < 4) {
            $date = strtotime('1-January-' . $date->format('Y'));
        } elseif ($month > 3 && $month < 7) {
            $date = strtotime('1-April-' . $date->format('Y'));
        } elseif ($month > 6 && $month < 10) {
            $date = strtotime('1-July-' . $date->format('Y'));
        } elseif ($month > 9) {
            $date = strtotime('1-October-' . $date->format('Y'));
        }

        return $date;
    }
}

if (!function_exists('checkIfTaxesAreApplicable')) {
    function checkIfTaxesAreApplicable(Model $model, int $country = 0, bool $isPurchaseOrder = false): bool
    {
        if ($isPurchaseOrder) {
            $documentCountry = $country;
        } else {
            $documentCountry = $model->project_id && $model->project->contact_id ?
            $model->project->contact->customer->billing_address->country : null;
        }

        $legalEntityCountry = $model->legal_entity_id ? $model->legalEntity->address->country : null;

        if (VatStatus::isAlways($model->vat_status)) {
            return true;
        } elseif (VatStatus::isNever($model->vat_status)) {
            return false;
        } elseif (!$isPurchaseOrder && $model->project->contact->customer->non_vat_liable) {
            return true;
        } elseif ($documentCountry && $legalEntityCountry && $documentCountry == $legalEntityCountry) {
            return true;
        }

        return false;
    }
}

if (!function_exists('getSettingsFormat')) {
    function getSettingsFormat(string $legalEntityId)
    {
        $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
        $legalEntity = $legalEntityRepository->firstById($legalEntityId, ['legalEntitySetting']);

        return $legalEntity->legalEntitySetting;
    }
}

if (!function_exists('getEndOfQuarter')) {
    function getEndOfQuarter(int $year, int $quarter)
    {
        if ($quarter == 1) {
            $date = strtotime('1-April-' . $year);
        } elseif ($quarter == 2) {
            $date = strtotime('1-July-' . $year);
        } elseif ($quarter == 3) {
            $date = strtotime('1-October-' . $year);
        } elseif ($quarter == 4) {
            $date = strtotime('1-January-' . ($year + 1));
        }

        return $date;
    }
}

if (!function_exists('convertTimestampToQuarterString')) {
    function convertTimestampToQuarterString(int $month, int $year): string
    {
        $thisQuarter = '';

        if ($month == 1) {
            $thisQuarter = 'Q1 ' . $year;
        } elseif ($month == 4) {
            $thisQuarter = 'Q2 ' . $year;
        } elseif ($month == 7) {
            $thisQuarter = 'Q3 ' . $year;
        } elseif ($month == 10) {
            $thisQuarter = 'Q4 ' . $year;
        }

        return $thisQuarter;
    }
}

if (!function_exists('getStartOfQuarter')) {
    function getStartOfQuarter(int $year, int $quarter)
    {
        if ($quarter == 1) {
            $date = strtotime('1-January-' . $year);
        } elseif ($quarter == 2) {
            $date = strtotime('1-April-' . $year);
        } elseif ($quarter == 3) {
            $date = strtotime('1-July-' . $year);
        } elseif ($quarter  == 4) {
            $date = strtotime('1-October-' . $year);
        }

        return $date;
    }
}

if (!function_exists('getAllSalespersonIds')) {
    /**
     * return array of salesperson ids of specified salesperson
     * @param string $salespersonId
     * @return array|null
     */
    function getAllSalespersonIds(string $salespersonId): ?array
    {
        $salesPerson = User::where('id', $salespersonId)->first();

        if ($salesPerson === null) {
            return null;
        }

        $salespersonIds = User::select('id')->where('email', $salesPerson->email)->pluck('id')->toArray();

        return $salespersonIds;
    }
}

if (!function_exists('filterUuidFormat')) {
    function filterUuidFormat(array $value): array
    {
        $filter = [];

        if (array_key_exists('id', $value)) {
            $filter[] = $value['id'];
        } else {
            $filter = $value;
        }

        return $filter;
    }
}

if (!function_exists('filterBooleanFormat')) {
    function filterBooleanFormat(array $value): array
    {
        $filter = [];

        foreach ($value as $val) {
            $filter[] = (bool) $val;
        }

        return $filter;
    }
}

if (!function_exists('isValidUuid')) {
    /**
     * Check if a given string is a valid UUID
     *
     * @param  string  $uuid   The string to check
     * @return bool
     */
    function isValidUuid(string $uuid): bool
    {
        if (!is_string($uuid) || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('checkCancelledStatus')) {
    function checkCancelledStatus(string $class, string $entityId): bool
    {
        $isCancelled = false;
        $model = $class::find($entityId);
        if ($model) {
            switch ($class) {
                case Quote::class:
                    $isCancelled = QuoteStatus::isCancelled($model->status);
                    break;
                case Order::class:
                    $isCancelled = OrderStatus::isCancelled($model->status);
                    break;
                case Invoice::class:
                    $isCancelled = InvoiceStatus::isCancelled($model->status);
                    break;
                case PurchaseOrder::class:
                    $isCancelled = PurchaseOrderStatus::isCancelled($model->status);
                    break;
            }
        }

        return $isCancelled;
    }
}

if (!function_exists('transformToEnum')) {
    function transformToEnum(string $value, string $enum): ?int
    {

        try {
            $result = $enum::make($value)->getIndex();
        } catch (\Exception $exception) {
            $result = null;
        }

        return $result;
    }
}

if (!function_exists('getPurchaseOrderDraftNumber')) {
    function getPurchaseOrderDraftNumber(): string
    {

        $draftPurchaseOrders = PurchaseOrder::where('number', 'like', 'Draft%')->orderByDesc('number')->get();

        if ($draftPurchaseOrders->isNotEmpty()) {
            $lastDraft = $draftPurchaseOrders->first();
            $lastNumber = str_replace('X', '', explode('-', $lastDraft->number)[1]);
        } else {
            $lastNumber = 0;
        }
        return transformFormat('DRAFT-XXXX', $lastNumber + 1);
    }
}

if (!function_exists('entityShadowPrices')) {
    function entityShadowPrices(string $class, string $id, string $companyId, $withTransactionFees = true)
    {
        $entity = $class::with('items', 'items.priceModifiers', 'priceModifiers')->findOrFail($id);
        $priceBeforeModifiers = 0;
        $priceIncludingPriceModifiers = 0;
        if (count($entity->items) > 0) {
            foreach ($entity->items as $item) {
                if ($item->company_id != $companyId) {
                    $price = itemPrice($item, false);
                    $priceBeforeModifiers += $price;
                    if (empty($item->exclude_from_price_modifiers)) {
                        $priceIncludingPriceModifiers += $price;
                    }
                }
            }

            if (count($entity->priceModifiers) > 0) {
                if (!empty($entity->project->price_modifiers_calculation_logic)) {
                    $priceBeforeModifiers = priceWithModifiersNewLogic($priceBeforeModifiers, $priceIncludingPriceModifiers, $entity->priceModifiers, 1, $withTransactionFees);
                } else {
                    $priceBeforeModifiers = priceWithModifiers($priceBeforeModifiers, $entity->priceModifiers, 1, $withTransactionFees);
                }
            }
        }

        return $priceBeforeModifiers;
    }
}

if (!function_exists('getModel')) {
    function getModel($tableName)
    {
        try {
            $modelName = ucfirst(Str::singular(Str::camel($tableName)));

            $modelClassName = "App\\Models\\$modelName";

            if (class_exists($modelClassName)) {
                return app($modelClassName);
            }
        } catch (\Throwable $e) {
            //
        }

        throw new \Exception("Model class $modelClassName does not exist.");
    }
}

if (!function_exists('getShadowsCosts')) {
    function getShadowsCosts($entityClass, $id)
    {
        $entity = $entityClass::find($id);
        $company = Company::find(getTenantWithConnection());
        $totalCosts = 0;
        $totalCostsUSD = 0;
        $totalPotentialCosts = 0;
        $totalPotentialCostsUSD = 0;
        $budget = 0;
        $budgetUsd = 0;
        foreach ($entity->shadows as $shadow) {
            $shadowCompany = Company::find($shadow->shadow_company_id);
            Tenancy::setTenant($shadowCompany);
            $shadowClassName = get_class($entity);
            $shadowEntity = $shadowClassName::find($shadow->shadow_id);
            $project = $shadowEntity->project;

            if (!empty($project)) {
                $po = $project->purchaseOrders()->whereIn('status', [
                PurchaseOrderStatus::submitted()->getIndex(),
                PurchaseOrderStatus::completed()->getIndex(), PurchaseOrderStatus::authorised()->getIndex(),
                PurchaseOrderStatus::billed()->getIndex(), PurchaseOrderStatus::paid()->getIndex()
                ])->get();
                $poPotentials = $project->purchaseOrders()->whereNotIn('status', [PurchaseOrderStatus::rejected()->getIndex(), PurchaseOrderStatus::cancelled()->getIndex()])->get();
                $poPotentialCosts = $poPotentials->sum('total_price') - $poPotentials->sum('total_vat');
                $poPotentialCostsUSD = $poPotentials->sum('total_price_usd') - $poPotentials->sum('total_vat_usd');
                $costs = $po->sum('total_price');
                $vat = $po->sum('total_vat');
                $costs_usd = $po->sum('total_price_usd');
                $vat_usd = $po->sum('total_vat_usd');
                $budget += $shadowEntity->total_price - $shadowEntity->total_vat;
                $budgetUsd += $shadowEntity->total_price_usd - $shadowEntity->total_vat_usd;
                $totalCosts += ($costs - $vat) + $project->employee_costs;
                $totalCostsUSD += ($costs_usd - $vat_usd) + $project->employee_costs_usd;
                $totalPotentialCosts += $poPotentialCosts + $project->employee_costs;
                $totalPotentialCostsUSD += $poPotentialCostsUSD + $project->employee_costs_usd;
            }
        }
        Tenancy::setTenant($company);

        return [
          'shadows_budget' => $budget,
          'shadows_budget_usd' => $budgetUsd,
          'shadows_total_potential_costs' => $totalPotentialCosts,
          'shadows_total_potential_costs_usd' => $totalPotentialCostsUSD,
          'shadows_total_costs' => $totalCosts,
          'shadows_total_costs_usd' => $totalCostsUSD
        ];
    }
}

if (!function_exists('getOrderEurToUsdRate')) {
    function getOrderEurToUsdRate(string $companyId, string $orderId): float
    {
        $company = Company::find($companyId);
        $order = Order::find($orderId);
        $rates = getCurrencyRates();
        $rateEurToUsd = $rates['rates']['USD'];

        if (empty($order->currency_rate_company)) {
            return $rateEurToUsd;
        } elseif ($company->currency_code == CurrencyCode::USD()->getIndex()) {
            return safeDivide(1, $order->currency_rate_company);
        } else {
            return $order->currency_rate_company;
        }
    }
}

if (!function_exists('randomDateBetween')) {
    function randomDateBetween(Carbon $startDate, Carbon $endDate)
    {
        $startTimestamp = $startDate->timestamp;
        $endTimestamp = $endDate->timestamp;
        $randomTimestamp = mt_rand($startTimestamp, $endTimestamp);

        return Carbon::createFromTimestamp($randomTimestamp);
    }
}

if (!function_exists('getEntityStats')) {
    function getEntityStats($entityClass, $id, $referenceEntity = null)
    {
        $entity = $entityClass::find($id);
        $companyCurrency = Company::find(getTenantWithConnection())->currency_code;
        if (!$entity->manual_input && $entity->currency_code != $companyCurrency) {
            $totalNoMods = ceiling(entityPrice($entityClass, $id, true, $entity->currency_rate_customer, false), 2);
            $totalWithMods = ceiling(entityPrice($entityClass, $id, false, $entity->currency_rate_customer, true), 2);
            $totalAffectedByPriceModifiers = ceiling(entity_price_affected_by_modifiers($entityClass, $id, $entity->currency_rate_customer), 2);
            $totalAmountWithoutVat = ceiling(entityPrice($entityClass, $id, false, $entity->currency_rate_customer, false), 2);
        } else {
            $totalNoMods = entityPrice($entityClass, $id, true, 1, false);
            $totalWithMods = entityPrice($entityClass, $id, false, 1, true);
            $totalAffectedByPriceModifiers = ceiling(entity_price_affected_by_modifiers($entityClass, $id, 1), 2);
            $totalAmountWithoutVat = ceiling(entityPrice($entityClass, $id, false, 1, false), 2);
        }

        if ($entity->total_vat == 0) {
            $vat = 0;
            $taxRate = 0;
        } else {
            $taxRate = empty($entity->vat_percentage) ? getTaxRate($entity->date, $entity->legal_entity_id) : $entity->vat_percentage;
            $vat = round($totalWithMods * ($taxRate / 100), 2);
        }

        $transactionFee = $entity->priceModifiers->first(function ($modifier) {
            return $modifier->description === EntityModifierDescription::transaction_fee()->__toString();
        });

        $transactionFeeAmount = 0;
        if (!empty($transactionFee) && $entity->project->price_modifiers_calculation_logic) {
            $transactionFeeAmount = $transactionFee->quantity * $totalAmountWithoutVat / 100;
        }

        $totalPrice = $entity->manual_input ? $entity->manual_price : $totalAmountWithoutVat + $vat + $transactionFeeAmount;
        $totalVat = $entity->manual_input ? $entity->manual_vat : $vat;

        return [
          'total_no_mods' => ceiling(convertEntityAmountToCompanyCurrencyCode($totalNoMods, $entity), 2),
          'total_with_mods' => ceiling(convertEntityAmountToCompanyCurrencyCode($totalWithMods, $entity), 2),
          'total_affected_by_price_modifiers' => ceiling(convertEntityAmountToCompanyCurrencyCode($totalAffectedByPriceModifiers, $entity), 2),
          'total_without_vat' => ceiling(convertEntityAmountToCompanyCurrencyCode($totalAmountWithoutVat, $entity), 2),
          'vat' => $vat,
          'tax_rate' => $taxRate,
          'transaction_fee_amount' => ceiling(convertEntityAmountToCompanyCurrencyCode($transactionFeeAmount, $entity), 2),
          'total_price'=>ceiling(convertEntityAmountToCompanyCurrencyCode($totalPrice, $entity), 2),
          'total_vat' => ceiling(convertEntityAmountToCompanyCurrencyCode($totalVat, $entity), 2)
        ];
    }
}

if (!function_exists('convertEntityAmount')) {
    function convertEntityAmount($amount, $fromEntity, $toEntity)
    {

        if (empty($fromEntity) || empty($toEntity)) {
            return $amount;
        }

        $company = Company::find(getTenantWithConnection());

        if ($company->currency_code == CurrencyCode::USD()->getIndex()) {
            $fromRate = $fromEntity->currency_rate_customer / ($fromEntity->currency_rate_company ?:1);
            $toRate = $toEntity->currency_rate_customer / ($toEntity->currency_rate_company ?:1);
        } else {
            $fromRate = $fromEntity->currency_rate_customer;
            $toRate = $toEntity->currency_rate_customer;
        }

        return ($amount / ($fromRate)) * $toRate;
    }
}

if (!function_exists('convertEntityAmountToCompanyCurrencyCode')) {
    function convertEntityAmountToCompanyCurrencyCode($amount, $entity, bool $manualInput = null)
    {

        if (empty($entity)) {
            return $amount;
        }

        if ($manualInput === null) {
            $manualInput = $entity->manual_input;
        }

        $company = Company::find(getTenantWithConnection());

        if ($manualInput) {
            return $amount;
        } else {
            if ($company->currency_code == CurrencyCode::USD()->getIndex()) {
                $rate = 1 / ($entity->currency_rate_customer ?:1);
            } else {
                $rate = 1 / ($entity->currency_rate_customer ?:1) ;
            }
        }

        return $amount * $rate;
    }
}

if (!function_exists('getCurrencyRates')) {
    /**
     * @return array
     * @throws \RuntimeException
     */
    function getCurrencyRates():array
    {
        if (empty(Cache::store('file')->get('rates')) && App::environment('production')) {
            throw new \RuntimeException('A problem happened during fetching api rates ...');
        }

        return Cache::store('file')->get('rates') ?? config('dev.rates');
    }
}

if (!function_exists('applyPenalty')) {
    function applyPenalty($amount, ?PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder && $purchaseOrder->penalty) {
            if ($purchaseOrder->penalty_type == EntityPenaltyType::fixed()->getIndex()) {
                return $amount -  $purchaseOrder->penalty;
            } else {
                $penalty = (100 - $purchaseOrder->penalty) / 100;
                return  $amount * $penalty;
            }
        }

        return $amount;
    }
}


if (!function_exists('sortListByIndex')) {
    function sortListByIndex(array $orderedList = [], array $unorderedList = []): array
    {
        if (empty($orderedList)) {
            return $unorderedList;
        }

        $orderMap = array_flip($orderedList);

        usort($unorderedList, function ($a, $b) use ($orderMap) {
            if (isset($orderMap[$a]) && isset($orderMap[$b])) {
                return $orderMap[$a] - $orderMap[$b];
            } else {
                return 0;
            }
        });

        return $unorderedList;
    }
}

if (!function_exists('extractQueryDocs')) {
    function extractQueryDocs(array $result, array $path = [], $conditions = []): array
    {
        $stats = ['doc_count' => 0, 'monetary_value' => 0, 'vat_value' => 0];
        if (isset($result['buckets'])) {
            $data = $result['buckets'];
        } else {
            $data = $result;
        }
        foreach ($data as $key => $value) {
            if (!empty($conditions) && is_array($conditions)) {
                foreach ($conditions as $conditionField => $condition) {
                    if (isset($value[$conditionField]) && $value[$conditionField] != $condition) {
                        continue;
                    }
                }
            }
            if (isset($stats[$key]) && is_numeric($value)) {
                $stats[$key] += $value;
            } elseif (isset($stats[$key]) && isset($value['value'])) {
                $stats[$key] += $value['value'];
            } else {
                $conditions = [];
                if (!empty($path)) {
                    if (array_values($path)[0] == $key) {
                        unset($path[0]);
                    } elseif (isset($path[$key])) {
                        $conditions = $path[$key];
                        unset($path[$key]);
                    }
                }

                if (is_array($value)) {
                    $statusValues = extractQueryDocs($value, $path, $conditions ?? []);
                    if (empty($path)) {
                        foreach (array_keys($stats) as $key2) {
                              $stats[$key2] += $statusValues[$key2];
                        }
                    }
                }
            }
        }
        return $stats;
    }
}

if (!function_exists('convertToBool')) {

    function convertToBool($string): ?bool
    {
        if (is_bool($string)) {
            return $string;
        }

        if (is_string($string)) {
            if (!$string || $string === 'null') {
                return null;
            }

            $boolValue = (bool) $string;

            return $boolValue;
        }

        return !empty($string);
    }
}

if (!function_exists('getFormatedFieldsWithUserCurrency')) {
    function getFormatedFieldsWithUserCurrency()
    {
        if (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex()) {
            $total_price = 'total_price';
            $total_vat = 'total_vat';
            $project_costs = 'project_info.po_cost';
            $project_vat = 'project_info.po_vat';
            $project_employee = 'project_info.employee_cost';
            $shadow_price = 'shadow_price';
            $shadow_vat = 'shadow_vat';
            $total_paid_amount = 'total_paid_amount';
            $total_paid_vat = 'total_paid_vat';
        } else {
            $total_price = 'total_price_usd';
            $total_vat = 'total_vat_usd';
            $project_costs = 'project_info.po_cost_usd';
            $project_vat = 'project_info.po_vat_usd';
            $project_employee = 'project_info.employee_cost_usd';
            $shadow_price = 'shadow_price_usd';
            $shadow_vat = 'shadow_vat_usd';
            $total_paid_amount = 'total_paid_amount_usd';
            $total_paid_vat = 'total_paid_vat_usd';
        }
        return compact('total_price', 'total_vat', 'project_costs', 'project_vat', 'project_employee', 'shadow_price', 'shadow_vat', 'total_paid_amount', 'total_paid_vat');
    }
}


if (!function_exists('subtractArrayValues')) {
    /**
     * Subtracts values of matching keys in two associative arrays.
     *
     * @param array $array1 The primary array from which values will be subtracted.
     * @param array $array2 The array containing values to subtract.
     * @return array The resulting array after subtraction.
     */
    function subtractArrayValues(array $array1, array $array2): array
    {
        $result = [];

        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    if (isset($array2[$key][$key2])) {
                        $result[$key][$key2] = $value2 - $array2[$key][$key2];
                    } else {
                        $result[$key][$key2] = $value2;
                    }
                }
            } else {
                if (isset($array2[$key])) {
                    $result[$key] = $value - $array2[$key];
                } else {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }
}

if (!function_exists('isColumnFiltered')) {
    function isColumnFiltered($filters = [], $prop)
    {

        if (!empty($filters)) {
            foreach ($filters as $filter) {
                if ($filter['prop'] === $prop) {
                    return true;
                }
            }
        }
        return false;
    }
}

if (!function_exists('generateProjectUniqueName')) {
    function generateProjectUniqueName(string $contactId): string
    {
        $contact = Contact::find($contactId);
        if (!empty($contact)) {
            $contactIds = $contact->customer->contacts()->get()->pluck('id')->toArray();
            $amountOfProjectsForCustomer = Project::whereIn('contact_id', $contactIds)->count() + 1;
            return $contact->customer->name . '_' . $amountOfProjectsForCustomer;
        }
        return 'Default project name';
    }
}

if (!function_exists('safeDivide')) {
    function safeDivide($dividend, $divider, ?float $default = null): ?float
    {
        if (empty($dividend)) {
            return $default ?? 0;
        }
        return (!empty($divider)) ? ($dividend / $divider) : ($default ?? $dividend);
    }
}

if (!function_exists('shareOrderGrossMargin')) {
    function shareOrderGrossMargin(Order $order)
    {
        $invoices = $order->invoices()->where([
          ['type', InvoiceType::accrec()->getIndex()],
          ['status', InvoiceStatus::paid()->getIndex()]
        ])->orderByDesc('updated_at')->get();
        foreach ($invoices as $invoice) {
            $companyId = getTenantWithConnection();
            ElasticUpdateAssignment::dispatch($companyId, Invoice::class, $invoice->id)->onQueue('low');
        }
    }
}

if (!function_exists('isIntraCompany')) {
    function isIntraCompany($customerId): bool
    {
        if ($customerId) {
            $customer = Customer::find($customerId);
            return $customer->intra_company;
        }
        return false;
    }
}

if (!function_exists('formatMoneyValue')) {
    function formatMoneyValue(float $value, string $currency): string
    {
        $currency = strtoupper($currency);
        $currencyFormatter = new \NumberFormatter('en-US', \NumberFormatter::CURRENCY);
        $amountFormatted = $currencyFormatter->formatCurrency($value, $currency);
        return $amountFormatted;
    }
}

if (!function_exists('getName')) {
    function getName($user)
    {
        return $user->first_name.' '.$user->last_name;
    }
}

if (!function_exists('getNames')) {
    function getNames($users)
    {
        $names = $users->map(function ($user) {
            return $user->name;
        });
         return implode(', ', $names->toArray());
    }
}

if (!function_exists('isSalesPersonAttached')) {
    function isSalesPersonAttached($userId, $projectId)
    {
        $salesPerson = User::findOrfail($userId);
        return $salesPerson->saleProjects()->where('project_id', $projectId)->exists();
    }
}

if (!function_exists('isLeadGenAttached')) {
    function isLeadGenAttached($userId, $projectId)
    {
        $salesPerson = User::findOrfail($userId);
        return $salesPerson->leadGenProjects()->where('project_id', $projectId)->exists();
    }
}

if (!function_exists('isSalesPersonAttachedToQuote')) {
    function isSalesPersonAttachedToQuote($quoteId, $userId)
    {
        $salesPerson = User::findOrfail($userId);
        return $salesPerson->leadGenProjects()->where('project_id', $quoteId)->exists();
    }
}

if (!function_exists('calculateGrossMargin')) {
    function calculateGrossMargin($invoice): float
    {

        $shadowTotalCosts = 0;
        $costs = 0;
        $budget = 0;
        $vat = 0;
        $markup = 0;
        if ($invoice->project) {
            $po = $invoice->order->project->purchaseOrders()->whereIn('status', [PurchaseOrderStatus::submitted()->getIndex(),
            PurchaseOrderStatus::authorised()->getIndex(), PurchaseOrderStatus::billed()->getIndex(),
            PurchaseOrderStatus::paid()->getIndex(), PurchaseOrderStatus::completed()->getIndex()])->get();
            $costs = $po->sum('total_price');
            $vat = $po->sum('total_vat');
            $budget = $invoice->total_price - $invoice->total_vat;
            if ($invoice->master) {
                $shadowsCosts = getShadowsCosts(Order::class, $invoice->order->id);
                $shadowTotalCosts = $shadowsCosts['shadows_total_costs'];
            }
        }

        if ($invoice->order && InvoiceType::isAccrec($invoice->type)) {
            $invoices = $invoice->order->invoices()->where([
            ['type', InvoiceType::accrec()->getIndex()],
            ['status', InvoiceStatus::paid()->getIndex()],
            ])->orderByDesc('updated_at')->get();
            $totalRevenue = $invoices->sum('total_price') - $invoices->sum('total_vat');
            $invoiceRevenuePercentage = safeDivide($budget, $totalRevenue);
            $totalCosts = ($costs - $vat) + $invoice->project->employee_costs + $shadowTotalCosts;
            $invoiceTotalCost = $totalCosts * $invoiceRevenuePercentage;
            $markup = $budget > 0 ? flooring(safeDivide($budget - $invoiceTotalCost, $budget) * 100, 2) : null;
        }

        return ($markup > 0  && $markup <= 100) ? $markup : 0;
    }
}

if (!function_exists('formatCommission')) {
    function formatCommission($invoice, $grossMargin, $invoicedTotalPaid, $percentage, $key, $value)
    {
        if (CommissionPercentageType::isCalculated($percentage->type)) {
            $commissionPercentage = QuoteService::getCommissionPercentage($key, $grossMargin);
            $commissionAmount = QuoteService::calculateCommission($commissionPercentage, $value);
        } else {
            $commissionPercentage = $percentage->commission_percentage;
            $commissionAmount = QuoteService::calculateCommission($commissionPercentage, $invoicedTotalPaid);
        }
        $percentageSalesPerson = User::find($percentage->sales_person_id);
        $payValueAt = Commission::getPaidValue($percentage->sales_person_id, $invoice['order_id'], $invoice['id']);
        $commission['project_id'] = $invoice['project_id'];
        $commission['invoice_status'] = $invoice['status'];
        $commission['order_status'] = $invoice['order_status'];
        $commission['invoice_id'] = $invoice['id'];
        $commission['order_id'] = $invoice['order_id'];
        $commission['invoice'] = $invoice['number'];
        $commission['order'] = $invoice['order_number'];
        $commission['commission_percentage_id'] = $percentage->id;
        $commission['gross_margin'] = $grossMargin;
        $commission['sales_person_commission'] = $key;
        $commission['commission_percentage'] = $commissionPercentage;
        $commission['commission'] = $commissionAmount;
        $commission['sales_person'] = $percentageSalesPerson->name;
        $commission['sales_person_id'] = $percentage->sales_person_id;
        $commission['total'] = round($value, 2);
        $commission['total_paid_amount'] = $invoicedTotalPaid;
        $commission['total_price'] = round($invoice['total_price'], 2);
        $commission['showActions'] = false;
        $commission['paid_value'] = $payValueAt['paid_value'];
        $commission['paid_at'] = $payValueAt['paid_at'];
        $commission['status'] = Commission::getStatus($commission['paid_value'], $commission['commission']);

        return $commission;
    }
}
