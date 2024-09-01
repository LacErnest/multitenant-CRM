<?php

namespace App\Http\Controllers\LegalEntity;

use App\DTO\Addresses\AddressDTO;
use App\DTO\LegalEntities\LegalEntityDTO;
use App\Enums\TablePreferenceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\LegalEntity\LegalEntityCreateRequest;
use App\Http\Requests\LegalEntity\LegalEntityUpdateRequest;
use App\Http\Resources\LegalEntity\LegalEntityResource;
use App\Models\LegalEntity;
use App\Services\LegalEntityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

class LegalEntityController extends Controller
{
    protected LegalEntityService $legalEntityService;

    protected string $model = LegalEntity::class;

    public function __construct(LegalEntityService $legalEntityService)
    {
        $this->legalEntityService = $legalEntityService;
    }

    /**
     * Get all legal entities
     *
     * @param string $companyId
     * @param Request $request
     * @return array
     */
    public function index(string $companyId, Request $request): array
    {
        return parent::getAll($companyId, $request, TablePreferenceType::legal_entities()->getIndex());
    }

    /**
     * Get one legal entity
     *
     * @param string $companyId
     * @param string $legalEntityId
     * @param Request $request
     * @return JsonResponse
     */
    public function view(
        string $companyId,
        string $legalEntityId,
        Request $request
    ): JsonResponse {
        $legalEntity = $this->legalEntityService->getSingle($legalEntityId);

        return LegalEntityResource::make($legalEntity)->toResponse($request);
    }

    /**
     * Create a legal entity
     *
     * @param string $companyId
     * @param LegalEntityCreateRequest $legalEntityCreateRequest
     * @return JsonResponse
     */
    public function create(string $companyId, LegalEntityCreateRequest $legalEntityCreateRequest): JsonResponse
    {
        $legalEntityPayload = new LegalEntityDTO($legalEntityCreateRequest->validated());
        $legalEntity = $this->legalEntityService->create($legalEntityPayload);

        return LegalEntityResource::make($legalEntity)
          ->toResponse($legalEntityCreateRequest)
          ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a legal entity
     *
     * @param string $companyId
     * @param string $legalEntityId
     * @param LegalEntityUpdateRequest $legalEntityUpdateRequest
     * @return JsonResponse
     */
    public function update(
        string $companyId,
        string $legalEntityId,
        LegalEntityUpdateRequest $legalEntityUpdateRequest
    ): JsonResponse {
        $legalEntityPayload = new LegalEntityDTO($legalEntityUpdateRequest->validated());
        $legalEntity = $this->legalEntityService->update($legalEntityId, $legalEntityPayload);

        return LegalEntityResource::make($legalEntity->load('address', 'europeanBank', 'americanBank'))
          ->toResponse($legalEntityUpdateRequest);
    }

    /**
     * Delete a legal entity
     *
     * @param string $companyId
     * @param string $legalEntityId
     * @return JsonResponse
     */
    public function delete(
        string $companyId,
        string $legalEntityId
    ): JsonResponse {
        $this->legalEntityService->delete($legalEntityId);

        return response()->json([], Response::HTTP_NO_CONTENT);
    }

    /**
     * Is legal entity linked to Xero
     *
     * @param string $companyId
     * @param string $legalEntityId
     * @return JsonResponse
     */
    public function xeroLinked(string $companyId, string $legalEntityId): JsonResponse
    {
        $xeroLinked = $this->legalEntityService->xeroLinked($legalEntityId);

        return response()->json(['is_xero_linked' => $xeroLinked]);
    }
}
