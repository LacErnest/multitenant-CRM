<?php

namespace App\Services;

use App\Contracts\Repositories\BankRepositoryInterface;
use App\Contracts\Repositories\CustomerAddressRepositoryInterface;
use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Contracts\Repositories\LegalEntitySettingRepositoryInterface;
use App\DTO\Addresses\AddressDTO;
use App\DTO\LegalEntities\LegalEntityDTO;
use App\Models\LegalEntity;
use App\Repositories\LegalEntityTemplateRepository;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psy\Util\Json;

/**
 * Class LegalEntityService
 * Manage legal entities
 */
class LegalEntityService
{
    protected LegalEntityRepositoryInterface $legalEntityRepository;

    protected CustomerAddressRepositoryInterface $addressRepository;

    protected BankRepositoryInterface $bankRepository;

    public function __construct(
        LegalEntityRepositoryInterface $legalEntityRepository,
        CustomerAddressRepositoryInterface $addressRepository,
        BankRepositoryInterface $bankRepository
    ) {
        $this->legalEntityRepository = $legalEntityRepository;
        $this->addressRepository = $addressRepository;
        $this->bankRepository = $bankRepository;
    }

    public function getSingle(string $legalEntityId): LegalEntity
    {
        return $this->legalEntityRepository->firstById($legalEntityId, [], ['*'], true);
    }

    public function create(
        LegalEntityDTO $createLegalEntityDTO
    ): LegalEntity {
        $legalEntitySettingsService = App::make(LegalEntitySettingsService::class);
        $legalEntityTemplateService = App::make(LegalEntityTemplateService::class);

        DB::beginTransaction();

        try {
            $address = $this->addressRepository->create($createLegalEntityDTO->legal_entity_address->toArray());
            $europeanBankAddress = $this->addressRepository->create($createLegalEntityDTO->european_bank->bank_address->toArray());
            $americanBankAddress = $createLegalEntityDTO->american_bank ?
            $this->addressRepository->create($createLegalEntityDTO->american_bank->bank_address->toArray()) : null;

            $europeanBank = $this->bankRepository->create(
                array_merge($createLegalEntityDTO->european_bank->toArray(), [
                'address_id' => $europeanBankAddress->id,
                ])
            );
            $americanBank = $createLegalEntityDTO->american_bank ? $this->bankRepository->create(
                array_merge($createLegalEntityDTO->american_bank->toArray(), [
                'address_id' => $americanBankAddress->id,
                ])
            ) : null;

            $payload = [
              'quote_number'                      => 0,
              'quote_number_format'               => 'Q-[YYYY][MM]XXXX',
              'order_number'                      => 0,
              'order_number_format'               => 'O-[YYYY][MM]XXXX',
              'invoice_number'                    => 0,
              'invoice_number_format'             => 'I-[YYYY][MM]XXXX',
              'purchase_order_number'             => 0,
              'purchase_order_number_format'      => 'PO-[YYYY][MM]XXXX',
              'resource_invoice_number'           => 0,
              'resource_invoice_number_format'    => 'RI-[YYYY][MM]XXXX',
            ];
            $legalEntitySetting = $legalEntitySettingsService->createSettingsForLegalEntity($payload);

            $legalEntity = $this->legalEntityRepository->create(
                array_merge($createLegalEntityDTO->toArray(), [
                'address_id' => $address->id,
                'european_bank_id' => $europeanBank->id,
                'american_bank_id' => $americanBank ? $americanBank->id : null,
                'legal_entity_setting_id' => $legalEntitySetting->id,
                ])
            );

            $legalEntityTemplateService->addDefaultTemplates($legalEntity->id);

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();

            throw $exception;
        }

        return $legalEntity;
    }

    public function update(
        string $legalEntityId,
        LegalEntityDTO $updateLegalEntityDTO
    ): LegalEntity {
        DB::beginTransaction();

        try {
            $this->legalEntityRepository->update($legalEntityId, $updateLegalEntityDTO->toArray());
            $legalEntity = $this->legalEntityRepository->firstById($legalEntityId);
            $legalEntity->address()->update($updateLegalEntityDTO->legal_entity_address->toArray());
            $legalEntity->europeanBank()->update(
                Arr::only($updateLegalEntityDTO->european_bank->toArray(), ['name', 'bic', 'iban'])
            );
            $legalEntity->europeanBank->address()->update($updateLegalEntityDTO->european_bank->bank_address->toArray());
            if ($updateLegalEntityDTO->american_bank) {
                if ($legalEntity->americanBank) {
                    $legalEntity->americanBank()->update(
                        Arr::only($updateLegalEntityDTO->american_bank->toArray(), ['name', 'account_number', 'routing_number', 'usa_account_number', 'usa_routing_number'])
                    );
                    $legalEntity->americanBank->address()->update($updateLegalEntityDTO->american_bank->bank_address->toArray());
                } else {
                    $americanBankAddress = $this->addressRepository->create($updateLegalEntityDTO->american_bank->bank_address->toArray());
                    $americanBank = $this->bankRepository->create(
                        array_merge($updateLegalEntityDTO->american_bank->toArray(), [
                        'address_id' => $americanBankAddress->id,
                        ])
                    );
                    $this->legalEntityRepository->update($legalEntityId, ['american_bank_id' => $americanBank->id]);
                    $legalEntity->refresh();
                }
            }

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();

            throw $exception;
        }

        return $legalEntity;
    }

    public function delete(string $legalEntityId): void
    {
        $this->legalEntityRepository->delete($legalEntityId);
    }

    public function xeroLinked(string $legalEntityId): bool
    {
        $legalEntity = $this->legalEntityRepository->firstById($legalEntityId);

        return $legalEntity->legal_entity_xero_config_id ? true : false;
    }
}
