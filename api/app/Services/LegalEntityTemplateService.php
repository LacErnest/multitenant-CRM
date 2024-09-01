<?php

namespace App\Services;

use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Contracts\Repositories\LegalEntitySettingRepositoryInterface;
use App\Contracts\Repositories\SettingRepositoryInterface;
use App\Enums\TemplateType;
use App\Models\GlobalMedia;
use App\Repositories\LegalEntityTemplateRepository;
use App\Repositories\TemplateRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Phpdocx\Create\CreateDocx;

/**
 * Class LegalEntityTemplateService
 * Service to handle legal entity templates, like contract, freelancer, employee
 */
class LegalEntityTemplateService
{
    protected string $appUrl;

    protected LegalEntityTemplateRepository $legalEntityTemplateRepository;

    protected LegalEntityRepositoryInterface $legalEntityRepository;

    protected LegalEntitySettingRepositoryInterface $legalEntitySettingsRepository;

    private CreateDocx $docx;

    private array $templates;

    public function __construct(
        LegalEntityTemplateRepository $legalEntityTemplateRepository,
        LegalEntityRepositoryInterface $legalEntityRepository,
        LegalEntitySettingRepositoryInterface $legalEntitySettingsRepository
    ) {
        $this->legalEntityTemplateRepository = $legalEntityTemplateRepository;
        $this->legalEntityRepository = $legalEntityRepository;
        $this->legalEntitySettingsRepository = $legalEntitySettingsRepository;
        $this->appUrl = config('app.url');
        $this->docx = App::make(CreateDocx::class);
        $this->templates = [
          TemplateType::customer()->getValue(),
          TemplateType::contractor()->getValue(),
          TemplateType::NDA()->getValue(),
          TemplateType::freelancer()->getValue(),
          TemplateType::employee()->getValue(),
        ];
    }

    public function index(string $legalEntityId): array
    {
        $array = [];
        $legalEntity = $this->legalEntityRepository->firstById($legalEntityId);
        $legalSetting = $this->legalEntitySettingsRepository->firstById($legalEntity->legal_entity_setting_id);

        foreach ($this->templates as $value) {
            if ($legalSetting->getMedia('templates_' . $value)->count()) {
                if ($value == TemplateType::NDA()->getValue() || $value == TemplateType::contractor()->getValue()
                || $value == TemplateType::freelancer()->getValue()) {
                    $classNamePart = 'resource';
                } else {
                    $classNamePart = $value;
                }

                $class = 'App\\Services\\Export\\' . Str::studly($classNamePart) . 'Exporter';
                $array[$value]['link'] = $this->getTemplateUrl($value, $legalEntityId);
                $values = (new $class())->getVariables(false, 'document');

                $array[$value]['fields'] = array_map(function ($k, $v) {
                    return ['value' => $k, 'description' => $v];
                }, array_keys($values), $values);
            }
        }

        return $array;
    }

    public function download(string $legalEntityId, string $entity, string $type)
    {
        $legalEntity = $this->legalEntityRepository->firstById($legalEntityId);
        $legalSetting = $this->legalEntitySettingsRepository->firstById($legalEntity->legal_entity_setting_id);

        $templateWordPath = $legalSetting->getFirstMediaPath('templates_' . $entity);

        if ($type == 'docx') {
            return response()->download($templateWordPath, $entity . '.docx', [
            'Content-Type' => 'application/docx'
            ], 'inline');
        }

        $templatePdfPath = $this->getTemplatePdfPath($templateWordPath);

        $this->docx->transformDocument($templateWordPath, $templatePdfPath);

        return Response::make(file_get_contents($templatePdfPath), 200, [
          'Content-Type'        => 'application/pdf',
          'Content-Disposition' => 'inline; filename="' . $entity . '.pdf' . '"'
        ]);
    }

    public function update(string $legalEntityId, string $entity, array $attributes): void
    {
        $this->legalEntityTemplateRepository->updateTemplate($attributes, $entity, $legalEntityId);
    }

    public function addDefaultTemplates(string $legalEntityId)
    {
        foreach ($this->templates as $template) {
            $defaultTemplate = $this->getDefaultTemplate($template);
            $this->legalEntityTemplateRepository->updateTemplate([
            'file' => base64_encode($defaultTemplate)
            ], $template, $legalEntityId);
        }
    }

    public function moveFormerTemplatesToLegalEntity(string $settingId, string $legalEntityId): void
    {
        $settingRepository = App::make(SettingRepositoryInterface::class);
        $setting = $settingRepository->firstById($settingId);

        foreach ($this->templates as $value) {
            if ($setting->getMedia('templates_' . $value)->count()) {
                $oldTemplate = $setting->getFirstMediaPath('templates_' . $value);
                $this->legalEntityTemplateRepository->addTemplate($oldTemplate, $value, $legalEntityId);
                $setting->getFirstMedia('templates_' . $value)->delete();
            } else {
                $defaultTemplate = $this->getDefaultTemplate($value);
                $this->legalEntityTemplateRepository->updateTemplate([
                'file' => base64_encode($defaultTemplate)
                ], $value, $legalEntityId);
            }
        }
    }

    public function restoreFormerTemplatesFromLegalEntity(string $settingId, string $legalEntityId): void
    {
        $settingRepository = App::make(SettingRepositoryInterface::class);
        $setting = $settingRepository->firstById($settingId);
        $templateRepository = App::makeWith(TemplateRepository::class, ['setting' => $setting]);

        $legalEntity = $this->legalEntityRepository->firstById($legalEntityId);
        $legalEntitySetting = $this->legalEntitySettingsRepository->firstById($legalEntity->legal_entity_setting_id);

        foreach ($this->templates as $value) {
            config()->set('media-library.media_model', GlobalMedia::class);

            if ($legalEntitySetting->getMedia('templates_' . $value)->count()) {
                $legalTemplate = $legalEntitySetting->getFirstMediaPath('templates_' . $value);
                $templateRepository->restoreTemplatesAfterRollback($legalTemplate, $value);
                $legalEntitySetting->getFirstMedia('templates_' . $value)->delete();
            }
        }
    }

    private function getTemplateUrl(string $entity, string $legalEntityId): string
    {
        return sprintf('%s/api/legal_entities/%s/templates/%s', $this->appUrl, $legalEntityId, $entity);
    }

    private function getTemplatePdfPath($path): string
    {
        return sprintf('%s/%s.pdf', pathinfo($path)['dirname'], pathinfo($path)['filename']);
    }

    private function getDefaultTemplate(string $template_name): string
    {
        return Storage::disk('templates')->get($template_name . '.docx');
    }
}
