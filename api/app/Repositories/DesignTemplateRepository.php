<?php


namespace App\Repositories;

use App\Models\DesignTemplate;
use App\Utils\ImageUtils;


class DesignTemplateRepository
{
    protected string $appUrl;

    public function __construct()
    {
        $this->appUrl = config('app.url');
    }


    /**
     * Save the design template design
     * @param DesignTemplate $designTemplate
     * @param string $emailContent
     * @return void
     */
    public function saveDesignTemplate(DesignTemplate $designTemplate, string $emailContent): void
    {
        $designTemplate->clearMediaCollection('design_template');
        $designTemplate
            ->addMediaFromString($emailContent)
            ->setFileName($designTemplate->id . '.json')
            ->setName($designTemplate->id)
            ->toMediaCollection('design_template')
            ->save();
    }

    /**
     * Get design template design path
     * @param DesignTemplate $designTemplate
     * @param bool $catched
     * @return ?string
     * @throws \Exception
     */
    public function getDesignTemplatePath(DesignTemplate $designTemplate, $catched = false): ?string
    {
        $templatePath = $designTemplate->getFirstMediaPath('design_template');
        if (!empty($templatePath)) {
            return pathinfo($templatePath)['dirname'] . '/' . pathinfo($templatePath)['filename'] . '.json';
        }
        if (!$catched) {
            throw new \Exception('Design template not found.');
        }
    }

    /**
     * Get the design template html file path
     * @param DesignTemplate $designTemplate
     * @param bool $catched
     * @return ?string
     * @throws \Exception
     */
    public function getDesignTemplateHtmlPath(DesignTemplate $designTemplate, $catched = false): ?string
    {
        $templatePath = $designTemplate->getFirstMediaPath('design_template_html');
        if (!empty($templatePath)) {
            return pathinfo($templatePath)['dirname'] . '/' . pathinfo($templatePath)['filename'] . '.html';
        }
        if (!$catched) {
            throw new \Exception('Design template not found.');
        }
    }

    /**
     * Get the design template preview image path
     * @param DesignTemplate $designTemplate
     * @param bool $catched
     * @return ?string
     * @throws \Exception
     */
    public function getDesignTemplatePreviewPath(DesignTemplate $designTemplate, bool $catched = false): string
    {
        $designTemplateMedia = $designTemplate->getMedia('design_template_preview');
        if ($designTemplateMedia->last()) {
            $templatePath = $designTemplateMedia->last()->getPath();
            if (!empty($templatePath)) {
                return pathinfo($templatePath)['dirname'] . '/' . pathinfo($templatePath)['filename'] . '.html';
            }
        }
        if (!$catched) {
            throw new \Exception('Design template not found.');
        }
        return null;
    }

    /**
     * Generate and save design template as html file
     * @param DesignTemplate $designTemplate
     * @return void
     */
    public function saveDesignTemplateHtml(DesignTemplate $designTemplate, $htmlObject): void
    {
        $htmlContent = json_decode($htmlObject, true);

        if (!empty($htmlContent['html'])) {
            $designTemplate->clearMediaCollection('design_template_html');
            $designTemplate
                ->addMediaFromString($htmlContent['html'])
                ->setFileName($designTemplate->id . '.html')
                ->setName($designTemplate->id)
                ->toMediaCollection('design_template_html')
                ->save();
        }
    }

    /**
     * Generate image preview based on the design template
     * @param DesignTemplate $designTemplate
     * @param string $htmlObject
     * @return void
     */
    public function saveDesignTemplatePreview(DesignTemplate $designTemplate, string $htmlObject): void
    {
        $htmlContent = json_decode($htmlObject, true);

        if (!empty($htmlContent['html'])) {
            $base64Preview = ImageUtils::convertHtmlToBase64($htmlContent['html']);
            $designTemplate->clearMediaCollection('design_template_preview');
            $designTemplate
                ->addMediaFromBase64($base64Preview)
                ->setFileName($designTemplate->id . '_' . time() . '.png')
                ->setName($designTemplate->id)
                ->toMediaCollection('design_template_preview')
                ->save();
        }
    }

    /**
     * Clear media collections for current design template
     * @param DesignTemplate $designTemplate
     * @return void
     */
    public function clearMediaCollections(DesignTemplate $designTemplate)
    {
        $designTemplate->clearMediaCollection('design_template');
        $designTemplate->clearMediaCollection('design_template_html');
        $designTemplate->clearMediaCollection('design_template_preview');
    }
}
