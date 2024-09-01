<?php

namespace App\Services;

use App\Contracts\Repositories\XeroEntityStorageRepositoryInterface;
use App\Models\XeroEntityStorage;

/**
 * Class XeroEntityStorageService
 */
class XeroEntityStorageService
{
    /**
     * @var XeroEntityStorageRepositoryInterface $xeroLinkStorageRepository
     */
    private XeroEntityStorageRepositoryInterface $xeroLinkStorageRepository;

    public function __construct(
        XeroEntityStorageRepositoryInterface $xeroLinkStorageRepository
    ) {
        $this->xeroLinkStorageRepository = $xeroLinkStorageRepository;
    }

    public function getXeroLinkOfEntity(string $entityId, string $legalEntityId, string $modelType): ?XeroEntityStorage
    {
        return $this->xeroLinkStorageRepository->getXeroLinkOfEntity($entityId, $legalEntityId, $modelType);
    }

    public function create(array $payLoad): XeroEntityStorage
    {
        return $this->xeroLinkStorageRepository->create($payLoad);
    }
}
