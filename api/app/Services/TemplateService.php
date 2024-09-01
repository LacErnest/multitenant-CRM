<?php


namespace App\Services;

use App\Models\Template;
use App\Repositories\TemplateRepository;
use Illuminate\Http\Request;

class TemplateService
{
    protected TemplateRepository $template_repository;

    public function __construct(TemplateRepository $setting_repository)
    {
        $this->template_repository = $setting_repository;
    }

    public function addTemplateCategory(Request $request)
    {
        return $this->template_repository->addTemplateCategory($request->all());
    }

    public function updateTemplateCategory(Request $request, $templateId)
    {
        return $this->template_repository->updateTemplateCategory($request->all(), $templateId);
    }

    public function deleteTemplateCategory($templateId)
    {
        $this->template_repository->deleteTemplateCategory($templateId);
    }

    public function getAllTemplateCategories()
    {
        return $this->template_repository->getAllTemplateCategories();
    }

    /**
     * Get template document for the given identifier
     * @param string $template
     * @return Template
     */
    public function getTemplateById(string $templateId): Template
    {
        return Template::findOrFail($templateId);
    }

    public function getTemplate(string $templateId, string $entity, string $type)
    {
        $template = Template::findOrFail($templateId);
        return $this->template_repository->getTemplate($template, $entity, $type);
    }

    public function getTemplates(string $templateId)
    {
        $template = Template::findOrFail($templateId);
        return $this->template_repository->getTemplates($template);
    }

    public function updateTemplate(string $templateId, Request $request, $entity)
    {
        $template = Template::findOrFail($templateId);
        return $this->template_repository->updateTemplate($template, $request->all(), $entity);
    }
}
