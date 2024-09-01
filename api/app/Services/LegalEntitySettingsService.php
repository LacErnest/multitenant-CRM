<?php

namespace App\Services;

use App\Contracts\Repositories\BankRepositoryInterface;
use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\CustomerAddressRepositoryInterface;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\EmployeeRepositoryInterface;
use App\Contracts\Repositories\GlobalTaxRateRepositoryInterface;
use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Contracts\Repositories\LegalEntitySettingRepositoryInterface;
use App\Contracts\Repositories\LegalEntityXeroConfigRepositoryInterface;
use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Contracts\Repositories\PurchaseOrderRepositoryInterface;
use App\Contracts\Repositories\QuoteRepositoryInterface;
use App\Contracts\Repositories\ResourceRepositoryInterface;
use App\Contracts\Repositories\SettingRepositoryInterface;
use App\Contracts\Repositories\XeroEntityStorageRepositoryInterface;
use App\DTO\LegalEntities\LegalEntitySettingDTO;
use App\Enums\Country;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\LegalEntitySetting;
use App\Models\Resource;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LegalEntitySettingsService
{
    protected LegalEntitySettingRepositoryInterface $legalEntitySettingRepository;

    protected LegalEntityRepositoryInterface $legalEntityRepository;

    public function __construct(
        LegalEntitySettingRepositoryInterface $legalEntitySettingRepository,
        LegalEntityRepositoryInterface $legalEntityRepository
    ) {
        $this->legalEntitySettingRepository = $legalEntitySettingRepository;
        $this->legalEntityRepository = $legalEntityRepository;
    }

    public function view(string $legalEntityId): LegalEntitySetting
    {
        $legalEntity = $this->legalEntityRepository->firstById($legalEntityId);
        return $this->legalEntitySettingRepository->firstById($legalEntity->legal_entity_setting_id);
    }

    public function update(string $legalEntityId, LegalEntitySettingDTO $settingDTO): LegalEntitySetting
    {
        $errors = [];
        $requestSettingsNumberData = [
          'quote_number' => $settingDTO->quote_number,
          'order_number' => $settingDTO->order_number,
          'invoice_number' => $settingDTO->invoice_number,
          'purchase_order_number' => $settingDTO->purchase_order_number,
          'resource_invoice_number' => $settingDTO->resource_invoice_number,
          'invoice_payment_number' => $settingDTO->invoice_payment_number,
        ];
        $requestSettingsFormatData = [
          'quote_number_format' => $settingDTO->quote_number_format,
          'order_number_format' => $settingDTO->order_number_format,
          'invoice_number_format' => $settingDTO->invoice_number_format,
          'purchase_order_number_format' => $settingDTO->purchase_order_number_format,
          'resource_invoice_number_format' => $settingDTO->resource_invoice_number_format,
          'invoice_payment_number_format' => $settingDTO->invoice_payment_number_format,
        ];

        $legalEntity = $this->legalEntityRepository->firstById($legalEntityId, ['legalEntitySetting']);
        $currentSettingsData = [
          'quote_number' => $legalEntity->legalEntitySetting->quote_number,
          'quote_number_format' => $legalEntity->legalEntitySetting->quote_number_format,
          'order_number' => $legalEntity->legalEntitySetting->order_number,
          'order_number_format' => $legalEntity->legalEntitySetting->order_number_format,
          'invoice_number' => $legalEntity->legalEntitySetting->invoice_number,
          'invoice_number_format' => $legalEntity->legalEntitySetting->invoice_number_format,
          'purchase_order_number' => $legalEntity->legalEntitySetting->purchase_order_number,
          'purchase_order_number_format' => $legalEntity->legalEntitySetting->purchase_order_number_format,
          'resource_invoice_number' => $legalEntity->legalEntitySetting->resource_invoice_number,
          'resource_invoice_number_format' => $legalEntity->legalEntitySetting->resource_invoice_number_format,
          'invoice_payment_number' => $legalEntity->legalEntitySetting->invoice_payment_number,
          'invoice_payment_number_format' => $legalEntity->legalEntitySetting->invoice_payment_number_format,
        ];

        foreach ($requestSettingsNumberData as $numberData => $value) {
            if (!($value === null)) {
                $numberNotValid = $this->checkSettingNumber(
                    $value,
                    $requestSettingsFormatData[$numberData . '_format'],
                    $currentSettingsData[$numberData],
                    $currentSettingsData[$numberData . '_format'],
                    $numberData
                );

                if ($numberNotValid) {
                        $errors = array_merge($errors, $numberNotValid);
                }
            }
        }

        foreach ($requestSettingsFormatData as $formatData => $value) {
            if (!($value === null)) {
                $formatHasError = $this->checkUpdateFormat($value, $currentSettingsData[$formatData], $formatData);

                if (is_array($formatHasError)) {
                    $errors = array_merge($errors, $formatHasError);
                }
            }
        }

        if (0 < count($errors)) {
            throw ValidationException::withMessages($errors);
        }

        $this->legalEntitySettingRepository->update($legalEntity->legal_entity_setting_id, array_filter($settingDTO->toArray()));

        return $this->legalEntitySettingRepository->firstById($legalEntity->legal_entity_setting_id);
    }

    private function checkSettingNumber(
        string $requestNumber,
        string $requestFormat,
        string $oldNumber,
        string $oldFormat,
        string $field
    ) {
        if ($requestFormat != $oldFormat) {
            return false;
        }

        if ($requestNumber < $oldNumber) {
            return [$field => 'Setting the number lower without changing format is not allowed.'];
        }

        return false;
    }

    public function migrateFormerSettingsToLegalEntitySettings(): void
    {
        $settingRepository = App::make(SettingRepositoryInterface::class);
        $companyRepository = App::make(CompanyRepositoryInterface::class);
        $xeroRepository = App::make(LegalEntityXeroConfigRepositoryInterface::class);
        $companyLegalEntityService = App::make(CompanyLegalEntityService::class);
        $addressRepository = App::make(CustomerAddressRepositoryInterface::class);
        $bankRepository = App::make(BankRepositoryInterface::class);

        DB::beginTransaction();

        try {
            $settings = $settingRepository->getAll();

            if ($settings->isNotEmpty()) {
                $settings = $settings->first();
                $settingsPayload = [
                'quote_number'                      => $settings->quote_number,
                'quote_number_format'               => $settings->quote_number_format,
                'order_number'                      => $settings->order_number,
                'order_number_format'               => $settings->order_number_format,
                'invoice_number'                    => $settings->invoice_number,
                'invoice_number_format'             => $settings->invoice_number_format,
                'purchase_order_number'             => $settings->purchase_order_number,
                'purchase_order_number_format'      => $settings->purchase_order_number_format,
                'resource_invoice_number'           => $settings->resource_invoice_number,
                'resource_invoice_number_format'    => $settings->resource_invoice_number_format,
                ];
                $legalSetting = $this->legalEntitySettingRepository->create($settingsPayload);

                $company = $companyRepository->firstById(getTenantWithConnection());
                $xeroConfig = null;
                if ($company->xero_access_token) {
                    $xeroPayload = [
                    'xero_tenant_id'     => $company->xero_tenant_id,
                    'xero_access_token'  => $company->xero_access_token,
                    'xero_refresh_token' => $company->xero_refresh_token,
                    'xero_id_token'      => $company->xero_id_token,
                    'xero_oauth2_state'  => $company->xero_oauth2_state,
                    'xero_expires'       => $company->xero_expires,
                    ];
                    $xeroConfig = $xeroRepository->create($xeroPayload);
                }

                $legalAddress = $addressRepository->create(['country' => Country::Ireland()->getIndex()]);
                $europeanBankAddress = $addressRepository->create(['country' => Country::Ireland()->getIndex()]);

                $europeanBank = $bankRepository->create([
                'name'       => 'name of bank',
                'address_id' => $europeanBankAddress->id,
                ]);

                $legalEntityPayload = [
                  'name'                          => $company->name . '_default',
                  'address_id'                    => $legalAddress->id,
                  'european_bank_id'              => $europeanBank->id,
                  'legal_entity_setting_id'       => $legalSetting->id,
                  'legal_entity_xero_config_id'   => $xeroConfig ? $xeroConfig->id : null,
                ];
                $legalEntity = $this->legalEntityRepository->create($legalEntityPayload);
                $companyLegalEntityService->attach($company->id, $legalEntity->id);

                $this->addLegalEntityIdToCurrentDocuments($legalEntity->id);
                $this->setLegalEntityOnTaxRates($company->id, $legalEntity->id);
                $this->moveTemplatesToLegalEntity($settings->first()->id, $legalEntity->id);

                if ($legalEntity->legal_entity_xero_config_id) {
                    $this->setCustomersOnXeroStorage($legalEntity->id, $company->id);
                }
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    public function rollbackLegalEntitySettingsToFormer(): void
    {
        DB::beginTransaction();

        try {
            $settingRepository = App::make(SettingRepositoryInterface::class);
            $companyRepository = App::make(CompanyRepositoryInterface::class);

            $company = $companyRepository->firstById(getTenantWithConnection());
            $legalEntity = $this->legalEntityRepository
            ->firstBy('name', $company->name . '_default', ['legalEntitySetting', 'xeroConfig']);

            if ($legalEntity->legalEntitySetting()->exists()) {
                $payload = [
                'quote_number'                      => $legalEntity->legalEntitySetting->quote_number,
                'quote_number_format'               => $legalEntity->legalEntitySetting->quote_number_format,
                'order_number'                      => $legalEntity->legalEntitySetting->order_number,
                'order_number_format'               => $legalEntity->legalEntitySetting->order_number_format,
                'invoice_number'                    => $legalEntity->legalEntitySetting->invoice_number,
                'invoice_number_format'             => $legalEntity->legalEntitySetting->invoice_number_format,
                'purchase_order_number'             => $legalEntity->legalEntitySetting->purchase_order_number,
                'purchase_order_number_format'      => $legalEntity->legalEntitySetting->purchase_order_number_format,
                'resource_invoice_number'           => $legalEntity->legalEntitySetting->resource_invoice_number,
                'resource_invoice_number_format'    => $legalEntity->legalEntitySetting->resource_invoice_number_format,
                ];
                $settings = $settingRepository->getAll();
                $settingRepository->update($settings->first()->id, $payload);

                $this->restoreTemplatesToSettings($settings->first()->id, $legalEntity->id);
            }

            if ($legalEntity->xeroConfig()->exists()) {
                $payload = [
                'xero_tenant_id'     => $legalEntity->xeroConfig->xero_tenant_id,
                'xero_access_token'  => $legalEntity->xeroConfig->xero_access_token,
                'xero_refresh_token' => $legalEntity->xeroConfig->xero_refresh_token,
                'xero_id_token'      => $legalEntity->xeroConfig->xero_id_token,
                'xero_oauth2_state'  => $legalEntity->xeroConfig->xero_oauth2_state,
                'xero_expires'       => $legalEntity->xeroConfig->xero_expires,
                ];
                $companyRepository->update($company->id, $payload);
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    private function checkUpdateFormat(string $requestFormat, string $settingFormat, string $field)
    {
        preg_match_all('/X+/', $requestFormat, $matches, PREG_OFFSET_CAPTURE);

        if (0 < count($matches[0])) {
            $lastMatch = $matches[0][count($matches[0]) - 1];
            $requestFormat = substr_replace($requestFormat, 'X', $lastMatch[1], strlen($lastMatch[0]));
        } else {
            return [$field => 'Number format must contain at least one numeric identifier [X].'];
        }

        if (0 < $lastMatch[1]) {
            $beforeChar = $requestFormat[$lastMatch[1] - 1];

            if (is_numeric($beforeChar)) {
                return [$field => 'Number format cannot contain numbers right before numeric identifier [X].'];
            }
        }

        if ($lastMatch[1] < (strlen($requestFormat) - 1)) {
            $afterChar = $requestFormat[$lastMatch[1] + 1];

            if (is_numeric($afterChar)) {
                return [$field => 'Number format cannot contain numbers right after numeric identifier [X].'];
            }
        }

        preg_match_all('/X+/', $settingFormat, $matches, PREG_OFFSET_CAPTURE);
        $lastMatch = $matches[0][count($matches[0]) - 1];
        $settingFormat = substr_replace($settingFormat, 'X', $lastMatch[1], strlen($lastMatch[0]));

        return $requestFormat != $settingFormat;
    }

    private function addLegalEntityIdToCurrentDocuments(string $legalEntityId): void
    {
        $orderRepository = App::make(OrderRepositoryInterface::class);
        $quoteRepository = App::make(QuoteRepositoryInterface::class);
        $invoiceRepository = App::make(InvoiceRepositoryInterface::class);
        $purchaseOrderRepository = App::make(PurchaseOrderRepositoryInterface::class);
        $employeeRepository = App::make(EmployeeRepositoryInterface::class);
        $resourceRepository = App::make(ResourceRepositoryInterface::class);

        $orderIds = $orderRepository->getAll()->pluck('id')->toArray();
        $orderRepository->massUpdate($orderIds, ['legal_entity_id' => $legalEntityId]);

        $quoteIds = $quoteRepository->getAll()->pluck('id')->toArray();
        $quoteRepository->massUpdate($quoteIds, ['legal_entity_id' => $legalEntityId]);

        $invoiceIds = $invoiceRepository->getAll()->pluck('id')->toArray();
        $invoiceRepository->massUpdate($invoiceIds, ['legal_entity_id' => $legalEntityId]);

        $purchaseOrderIds = $purchaseOrderRepository->getAll()->pluck('id')->toArray();
        $purchaseOrderRepository->massUpdate($purchaseOrderIds, ['legal_entity_id' => $legalEntityId]);

        $employeeIds = $employeeRepository->getAll()->pluck('id')->toArray();
        $employeeRepository->massUpdate($employeeIds, ['legal_entity_id' => $legalEntityId]);

        $resourceIds = $resourceRepository->getAll()->pluck('id')->toArray();
        $resourceRepository->massUpdate($resourceIds, ['legal_entity_id' => $legalEntityId]);
    }

    private function moveTemplatesToLegalEntity(string $settingId, string $legalEntityId): void
    {
        $legalEntityTemplateService = App::make(LegalEntityTemplateService::class);

        $legalEntityTemplateService->moveFormerTemplatesToLegalEntity($settingId, $legalEntityId);
    }

    private function restoreTemplatesToSettings(string $settingId, string $legalEntityId): void
    {
        $legalEntityTemplateService = App::make(LegalEntityTemplateService::class);

        $legalEntityTemplateService->restoreFormerTemplatesFromLegalEntity($settingId, $legalEntityId);
    }

    private function setLegalEntityOnTaxRates(string $companyId, string $legalEntityId): void
    {
        $globalTaxRateRepository = App::make(GlobalTaxRateRepositoryInterface::class);
        $globalTaxRateRepository->updateBy('belonged_to_company_id', $companyId, ['legal_entity_id' => $legalEntityId]);
    }

    public function createSettingsForLegalEntity(array $payload): LegalEntitySetting
    {
        return $this->legalEntitySettingRepository->create($payload);
    }

    private function setCustomersOnXeroStorage(string $legalEntityId, string $companyId)
    {
        $xeroStorageRepository = App::make(XeroEntityStorageRepositoryInterface::class);
        $customerRepository = App::make(CustomerRepositoryInterface::class);
        $resourceRepository = App::make(ResourceRepositoryInterface::class);

        $customers = $customerRepository->getCompanyCustomersWithXero($companyId);

        foreach ($customers as $customer) {
            foreach ($customer->contacts as $contact) {
                $contactPayload = [
                'xero_id' => $customer->xero_id,
                'legal_entity_id' => $legalEntityId,
                'document_id' => $contact->id,
                'document_type' => Contact::class,
                ];
                $xeroStorageRepository->create($contactPayload);
            }
            $customerPayload = [
            'xero_id' => $customer->xero_id,
            'legal_entity_id' => $legalEntityId,
            'document_id' => $customer->id,
            'document_type' => Customer::class,
            ];
            $xeroStorageRepository->create($customerPayload);
        }

        $resources = $resourceRepository->getResourcesWithXero();
        foreach ($resources as $resource) {
            $resourcePayload = [
            'xero_id' => $resource->xero_id,
            'legal_entity_id' => $legalEntityId,
            'document_id' => $resource->id,
            'document_type' => Resource::class,
            ];
            $xeroStorageRepository->create($resourcePayload);
        }
    }
}
