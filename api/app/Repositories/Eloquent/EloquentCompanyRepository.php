<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Models\Company;

class EloquentCompanyRepository extends EloquentRepository implements CompanyRepositoryInterface
{
    public const CURRENT_MODEL = Company::class;

    public function checkIfAlreadyLinked(Company $company, string $legalEntityId): bool
    {
        return $company->legalEntities()->where('legal_entity_id', $legalEntityId)->exists();
    }

    public function checkIfFirstLinked(Company $company): bool
    {
        return $company->legalEntities->count() == 0;
    }

    public function linkLegalEntity(Company $company, string $legalEntityId, bool $default)
    {
        $company->legalEntities()->attach($legalEntityId, ['default' => $default]);

        return $company->legalEntities()->orderByDesc('pivot_created_at')->first();
    }

    public function checkIsDefault(Company $company, string $legalEntityId): bool
    {
        return $company->legalEntities()->where('legal_entity_id', $legalEntityId)
              ->first()->pivot->default == true;
    }

    public function unlinkLegalEntity(Company $company, string $legalEntityId): void
    {
        $company->legalEntities()->detach($legalEntityId);
    }

    public function setLegalEntityAsDefault(Company $company, string $legalEntityId): void
    {
        $company->legalEntities()->updateExistingPivot($company->legalEntities()
          ->wherePivot('default', true)->first()->pivot->legal_entity_id, [
          'default' => false,
        ]);
        $company->legalEntities()->updateExistingPivot($legalEntityId, [
          'default' => true,
        ]);
    }

    public function checkIsLocal(Company $company, string $legalEntityId): bool
    {
        return $company->legalEntities()->where('legal_entity_id', $legalEntityId)
              ->first()->pivot->local == true;
    }

    public function setLegalEntityAsLocal(Company $company, string $legalEntityId): void
    {
        $localLegal = $company->legalEntities()->wherePivot('local', true)->first();
        if ($localLegal) {
            $company->legalEntities()->updateExistingPivot($localLegal->pivot->legal_entity_id, [
            'local' => false,
            ]);
        }

        $company->legalEntities()->updateExistingPivot($legalEntityId, [
          'local' => true,
        ]);
    }
}
