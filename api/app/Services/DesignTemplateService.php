<?php

namespace App\Services;

use App\Contracts\Repositories\DesignTemplateRepositoryInterface;
use App\DTO\DesignTemplates\DesignTemplateDTO;
use App\Models\DesignTemplate;
use App\Models\EmailReminder;
use App\Models\EmailTemplate;
use App\Repositories\DesignTemplateRepository;
use Illuminate\Database\Eloquent\Collection;

class DesignTemplateService
{
    /**
     * @var DesignTemplateRepositoryInterface
     */
    protected DesignTemplateRepositoryInterface $designTemplateRepository;

    /**
     * @var DesignTemplateRepository
     */
    protected DesignTemplateRepository $utilsDesignTemplateRepository;

    /**
     * Design template constructor
     * @param DesignTemplateRepositoryInterface $designTemplateRepository
     */
    public function __construct(
        DesignTemplateRepositoryInterface $designTemplateRepository,
        DesignTemplateRepository $utilsDesignTemplateRepository
    ) {
        $this->designTemplateRepository = $designTemplateRepository;
        $this->utilsDesignTemplateRepository = $utilsDesignTemplateRepository;
    }

    /**
     * Get design template document for the given id
     * @param string $id
     * @return DesignTemplate
     */
    public function findById(string $id): DesignTemplate
    {
        return $this->designTemplateRepository->firstById($id);
    }

    /**
     * Get design template document for the given id
     * Return null if no design template founded
     * @param string $id
     * @return DesignTemplate | null
     */
    public function findByIdOrNull(string $id): ?DesignTemplate
    {
        return $this->designTemplateRepository->firstByIdOrNull($id);
    }

    /**
     * Get all design template documents ordered by creation date
     * @return Collection
     */
    public function getAll(): Collection
    {
        return DesignTemplate::orderByDesc('created_at')->get();
    }

    /**
     * Create new design template document
     * @param DesignTemplateDTO $templateDTO
     * @return DesignTemplate
     */
    public function create(DesignTemplateDTO $templateDTO): DesignTemplate
    {
        $designTemplate = $this->designTemplateRepository->create($templateDTO->toArray());
        $this->utilsDesignTemplateRepository->saveDesignTemplate($designTemplate, $templateDTO->design);
        $this->utilsDesignTemplateRepository->saveDesignTemplateHtml($designTemplate, $templateDTO->html);
        $this->utilsDesignTemplateRepository->saveDesignTemplatePreview($designTemplate, $templateDTO->html);
        return $designTemplate;
    }

    /**
     * Update existing design template document
     * @param string $id
     * @param DesignTemplateDTO $templateDTO
     * @return DesignTemplate
     */
    public function update(string $id, DesignTemplateDTO $templateDTO): DesignTemplate
    {
        $designTemplate = $this->designTemplateRepository->firstById($id);
        $designTemplate->update($templateDTO->toArray());
        $this->utilsDesignTemplateRepository->saveDesignTemplate($designTemplate, $templateDTO->design);
        $this->utilsDesignTemplateRepository->saveDesignTemplateHtml($designTemplate, $templateDTO->html);
        $this->utilsDesignTemplateRepository->saveDesignTemplatePreview($designTemplate, $templateDTO->html);
        return $this->findById($id);
    }

    /**
     * Delete existing design template document
     * @param string $id
     * @return int
     */
    public function delete(string $id): int
    {
        $designTemplate = $this->designTemplateRepository->firstById($id);
        $isConfiguredToBeUsed = EmailTemplate::whereHas('designTemplate', function ($query) use ($designTemplate) {
            return $query->where('design_template_id', $designTemplate->id);
        })->exists();

        $isConfiguredToBeUsed = $isConfiguredToBeUsed || EmailReminder::whereHas('designTemplate', function ($query) use ($designTemplate) {
            return $query->where('design_template_id', $designTemplate->id);
        })->exists();

        if ($isConfiguredToBeUsed) {
            throw new \Exception('Design template cannot be deleted. Some invoices are configured to be used.');
        }

        $this->utilsDesignTemplateRepository->clearMediaCollections($designTemplate);

        return $designTemplate->delete();
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
        return $this->utilsDesignTemplateRepository->getDesignTemplatePath($designTemplate, $catched);
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
        return $this->utilsDesignTemplateRepository->getDesignTemplateHtmlPath($designTemplate, $catched);
    }

    /**
     * Get the design template preview image path
     * @param DesignTemplate $designTemplate
     * @param bool $catched
     * @return ?string
     * @throws \Exception
     */
    public function getDesignTemplatePreviewPath(DesignTemplate $designTemplate, bool $catched = false): ?string
    {
        return $this->utilsDesignTemplateRepository->getDesignTemplatePreviewPath($designTemplate, $catched);
    }
}
