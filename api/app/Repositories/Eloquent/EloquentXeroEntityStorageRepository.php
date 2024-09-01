<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\XeroEntityStorageRepositoryInterface;
use App\Models\XeroEntityStorage;

class EloquentXeroEntityStorageRepository extends EloquentRepository implements XeroEntityStorageRepositoryInterface
{
    public const CURRENT_MODEL = XeroEntityStorage::class;

    protected XeroEntityStorage $xeroEntityStorage;

    public function __construct(XeroEntityStorage $xeroEntityStorage)
    {
        $this->xeroEntityStorage = $xeroEntityStorage;
    }

    public function getXeroLinkOfEntity(string $entityId, string $legalEntityId, string $modelType): ?XeroEntityStorage
    {
        return $this->xeroEntityStorage->where([['document_id', $entityId],
          ['document_type', $modelType], ['legal_entity_id', $legalEntityId]])->first();
    }
}
