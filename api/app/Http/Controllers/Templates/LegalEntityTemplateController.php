<?php

namespace App\Http\Controllers\Templates;

use App\Http\Controllers\Controller;
use App\Http\Requests\Templates\LegalEntityTemplateUpdateRequest;
use App\Services\LegalEntityTemplateService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LegalEntityTemplateController extends Controller
{
    protected LegalEntityTemplateService $legalEntityTemplateService;

    public function __construct(LegalEntityTemplateService $legalEntityTemplateService)
    {
        $this->legalEntityTemplateService = $legalEntityTemplateService;
    }

    /**
     * @param string $legalEntityId
     * @return array
     */
    public function index(string $legalEntityId): array
    {
        return $this->legalEntityTemplateService->index($legalEntityId);
    }

    /**
     * @param string $legalEntityId
     * @param string $entity
     * @param string $type
     * @return Response|BinaryFileResponse
     */
    public function download(string $legalEntityId, string $entity, string $type)
    {
        return $this->legalEntityTemplateService->download($legalEntityId, $entity, $type);
    }

    /**
     * @param string $legalEntityId
     * @param string $entity
     * @param LegalEntityTemplateUpdateRequest $legalEntityTemplateUpdateRequest
     */
    public function update(
        string $legalEntityId,
        string $entity,
        LegalEntityTemplateUpdateRequest $legalEntityTemplateUpdateRequest
    ): void {
        $this->legalEntityTemplateService->update($legalEntityId, $entity, $legalEntityTemplateUpdateRequest->validated());
    }
}
