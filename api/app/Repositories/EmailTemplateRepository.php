<?php


namespace App\Repositories;

use App\Enums\TemplateType;
use App\Models\EmailTemplate;
use App\Models\Media;
use App\Models\Template;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Phpdocx\Create\CreateDocx;
use Illuminate\Support\Facades\Response;
use Tenancy\Facades\Tenancy;

class EmailTemplateRepository
{
    protected EmailTemplate $template;
    protected string $appUrl;

    public function __construct(EmailTemplate $template)
    {
        $this->template = $template;
        $this->appUrl = config('app.url');
    }

    private function getTemplateUrl(string $entity, string $templateId)
    {
        return $this->appUrl . '/api/' . getTenantWithConnection() . '/templates/' .$templateId . '/' . $entity;
    }


    public function getTemplate(EmailTemplate $template, string $entity)
    {
        $templateWordPath = $template->getFirstMediaPath('templates_' . $entity);

        return response()->download($templateWordPath, $entity . '.html', [
            'Content-Type' => 'application/html'
            ], 'inline');
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
}
