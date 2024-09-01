<?php


namespace App\Support;


use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Models\LegalEntitySetting;
use Illuminate\Support\Facades\App;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

/**
 * Class TenantPathGenerator
 * @package App\Support
 *
 * This class makes sure that paths don't collide in our multi tenant environment
 */
class TenantPathGenerator extends DefaultPathGenerator
{
    protected function getBasePath(Media $media): string
    {
        if ($media->model_type == LegalEntitySetting::class) {
            $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
            $legalEntity = $legalEntityRepository->firstBy('legal_entity_setting_id', $media->model_id);

            return $legalEntity ? $legalEntity->id.DIRECTORY_SEPARATOR.$media->getKey() : $media->getKey();
        } else {
            return getTenantWithConnection() ? getTenantWithConnection().DIRECTORY_SEPARATOR.$media->getKey() : $media->getKey();
        }
    }
}
