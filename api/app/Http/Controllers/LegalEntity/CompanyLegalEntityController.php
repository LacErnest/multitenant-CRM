<?php

namespace App\Http\Controllers\LegalEntity;

use App\Http\Controllers\Controller;
use App\Http\Requests\LegalEntity\CompanyLegalEntityAttachRequest;
use App\Http\Resources\LegalEntity\CompanyLegalEntityResource;
use App\Http\Resources\LegalEntity\LegalEntityResource;
use App\Services\CompanyLegalEntityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CompanyLegalEntityController extends Controller
{
    protected CompanyLegalEntityService $companyLegalEntityService;

    public function __construct(CompanyLegalEntityService $companyLegalEntityService)
    {
        $this->companyLegalEntityService = $companyLegalEntityService;
    }

    /**
     * Get all linked legal entities for a company
     *
     * @param string $companyId
     * @return JsonResponse
     */
    public function index(string $companyId): JsonResponse
    {
        $companyLegals = $this->companyLegalEntityService->index($companyId);

        return response()->json(['count' => $companyLegals->count(), 'data' => CompanyLegalEntityResource::collection($companyLegals->sortBy('name'))]);
    }

    /**
     * Link a legal entity to a company
     *
     * @param string $companyId
     * @param string $legalEntityId
     * @param Request $request
     * @return JsonResponse
     */
    public function link(string $companyId, string $legalEntityId, Request $request): JsonResponse
    {
        $legalEntity = $this->companyLegalEntityService->attach($companyId, $legalEntityId);

        return CompanyLegalEntityResource::make($legalEntity)->toResponse($request);
    }

    /**
     * Unlink a legal entity from a company
     * @param string $companyId
     * @param string $legalEntityId
     * @return JsonResponse
     */
    public function unlink(string $companyId, string $legalEntityId): JsonResponse
    {
        $this->companyLegalEntityService->detach($companyId, $legalEntityId);

        return response()->json([], Response::HTTP_OK);
    }

    /**
     * Set a legal entity as the default one for a company
     *
     * @param string $companyId
     * @param string $legalEntityId
     * @return JsonResponse
     */
    public function setDefault(string $companyId, string $legalEntityId): JsonResponse
    {
        $this->companyLegalEntityService->setDefault($companyId, $legalEntityId);

        return response()->json('Legal entity set as default.');
    }

    /**
     * Auto-suggest for legal entities
     *
     * @param string $companyId
     * @param string $value
     * @return JsonResponse
     */
    public function suggest(string $companyId, string $value): JsonResponse
    {
        $result = $this->companyLegalEntityService->suggest($value);

        return response()->json(['suggestions' => $result ?? []]);
    }

    /**
     * Set a legal entity as the local one for a company
     *
     * @param string $companyId
     * @param string $legalEntityId
     * @return JsonResponse
     */
    public function setLocal(string $companyId, string $legalEntityId): JsonResponse
    {
        $this->companyLegalEntityService->setLocal($companyId, $legalEntityId);

        return response()->json('Legal entity set as local.');
    }
}
