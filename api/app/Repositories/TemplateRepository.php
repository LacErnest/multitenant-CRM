<?php


namespace App\Repositories;

use App\Enums\TemplateType;
use App\Models\Media;
use App\Models\Template;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Phpdocx\Create\CreateDocx;
use Illuminate\Support\Facades\Response;
use Tenancy\Facades\Tenancy;

class TemplateRepository
{
    protected Template $template;
    protected string $appUrl;
    private CreateDocx $docx;

    public function __construct(Template $template)
    {
        $this->template = $template;
        $this->appUrl = config('app.url');
        $this->docx = new CreateDocx();
    }

    private function getTemplateUrl(string $entity, string $templateId)
    {
        return $this->appUrl . '/api/' . getTenantWithConnection() . '/templates/' .$templateId . '/' . $entity;
    }

    public function addTemplateCategory($attributes)
    {
        $template = $this->template->create($attributes);
        (new TemplateRepository($template))->addDefaultTemplates($template);
        return $template;
    }

    public function updateTemplateCategory($attributes, $templateId)
    {
        $template = Template::findOrFail($templateId);
        $template->update($attributes);
        return $template->refresh();
    }

    public function deleteTemplateCategory($templateId)
    {
        $template = Template::findOrFail($templateId);
        $template->delete();
    }

    public function getAllTemplateCategories()
    {
        $templates = $this->template->all();
        return $templates;
    }

    public function getTemplate(Template $template, string $entity, string $type)
    {
        $templateWordPath = $template->getFirstMediaPath('templates_' . $entity);

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

    public function getTemplates(Template $template)
    {
        foreach (TemplateType::getValues() as $value) {
            if ($template->getMedia('templates_' . $value)->count()) {
                if ($value == 'NDA' || $value == 'contractor' || $value == 'freelancer') {
                    $classNamePart = 'resource';
                } else {
                    $classNamePart = $value;
                }

                $class = 'App\\Services\\Export\\' . Str::studly($classNamePart) . 'Exporter';
                $array[$value]['link'] = $this->getTemplateUrl($value, $template->id);
                $array[$value]['name'] = $template->getFirstMedia('templates_' . $value)->file_name ?? null;
                $values = (new $class())->getVariables(false, 'document');

                $array[$value]['fields'] = array_map(function ($k, $v) {
                    return ['value' => $k, 'description' => $v];
                }, array_keys($values), $values);
            }
        }

        return response()->json($array ?? []);
    }

    public function updateTemplate(Template $template, $attributes, $entity)
    {
        if (!in_array($entity, TemplateType::getValues())) {
            throw new ModelNotFoundException();
        }

        $template
          ->addMediaFromBase64($attributes['file'])
          ->setFileName($entity . '.docx')
          ->setName($entity)
          ->toMediaCollection('templates_' . $entity)
          ->save();

        return response()->json();
    }

    private function getTemplatePdfPath($path)
    {
        return pathinfo($path)['dirname'] . '/' . pathinfo($path)['filename'] . '.pdf';
    }

    private function getDefaultTemplate(string $template_name)
    {
        return Storage::disk('templates')->get($template_name . '.docx');
    }

    public function addDefaultTemplates(Template $template)
    {
        $entityTemplates = [
          'quote',
          'order',
          'invoice',
          'purchase_order',
        ];

        foreach ($entityTemplates as $value) {
            $default = $this->getDefaultTemplate($value);
            $this->updateTemplate($template, [
              'file' => base64_encode($default)
            ], $value);
        }
    }

    public function restoreTemplatesAfterRollback(string $pathToFile, string $entity)
    {
        if (!in_array($entity, TemplateType::getValues())) {
            throw new ModelNotFoundException();
        }

        config()->set('media-library.media_model', Media::class);
        $this->template->first()
          ->addMedia($pathToFile)
          ->setFileName($entity . '.docx')
          ->setName($entity)
          ->toMediaCollection('templates_' . $entity)
          ->save();
    }
}
