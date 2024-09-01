<?php

namespace App\Repositories;

use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Contracts\Repositories\LegalEntitySettingRepositoryInterface;
use App\Enums\TemplateType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

class LegalEntityTemplateRepository
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

    public function updateTemplate(array $attributes, string $entity, string $legalEntityId): void
    {
        if (!in_array($entity, TemplateType::getValues())) {
            throw new ModelNotFoundException();
        }

        $legalEntity = $this->legalEntityRepository->firstById($legalEntityId);
        $legalSetting = $this->legalEntitySettingRepository->firstById($legalEntity->legal_entity_setting_id);
        $legalSetting
          ->addMediaFromBase64($attributes['file'])
          ->setFileName($entity . '.docx')
          ->setName($entity)
          ->toMediaCollection('templates_' . $entity)
          ->save();
    }

    public function addTemplate(string $pathToFile, string $entity, string $legalEntityId): void
    {
        $legalEntity = $this->legalEntityRepository->firstById($legalEntityId);
        $legalSetting = $this->legalEntitySettingRepository->firstById($legalEntity->legal_entity_setting_id);
        $legalSetting
          ->addMedia($pathToFile)
          ->setFileName($entity . '.docx')
          ->setName($entity)
          ->toMediaCollection('templates_' . $entity)
          ->save();
    }
}
