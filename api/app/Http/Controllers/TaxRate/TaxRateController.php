<?php

namespace App\Http\Controllers\TaxRate;

use App\DTO\GlobalTaxRates\GlobalTaxRateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaxRate\CreateTaxRateRequest;
use App\Http\Requests\TaxRate\DeleteTaxRateRequest;
use App\Http\Requests\TaxRate\UpdateTaxRateRequest;
use App\Http\Resources\TaxRate\TaxRateResource;
use App\Services\TaxRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaxRateController extends Controller
{
    protected TaxRateService $taxRateService;

    public function __construct(TaxRateService $taxRateService)
    {
        $this->taxRateService = $taxRateService;
    }

    /**
     * Get all tax rates for a legal entity
     *
     * @param string $legalEntityId
     * @return JsonResponse
     */
    public function index(string $legalEntityId): JsonResponse
    {
        $taxRates = $this->taxRateService->index($legalEntityId);

        return response()->json(['count' => $taxRates->count(), 'data' => TaxRateResource::collection($taxRates)]);
    }

    /**
     * Get one specific tax rate of Ireland
     *
     * @param string $legalEntityId
     * @param string $taxRateId
     * @param Request $request
     * @return JsonResponse
     */
    public function view(string $legalEntityId, string $taxRateId, Request $request): JsonResponse
    {
        $taxRates = $this->taxRateService->view($legalEntityId, $taxRateId);

        return TaxRateResource::make($taxRates->first())->toResponse($request);
    }

    /**
     * Create a tax rate for a specific period
     *
     * @param string $legalEntityId
     * @param CreateTaxRateRequest $createTaxRateRequest
     * @return JsonResponse
     */
    public function create(string $legalEntityId, CreateTaxRateRequest $createTaxRateRequest): JsonResponse
    {
        $taxRateDTO = new GlobalTaxRateDTO($createTaxRateRequest->allSafe());
        $taxRateDTO->legal_entity_id = $legalEntityId;
        $taxRate = $this->taxRateService->create($taxRateDTO);

        return TaxRateResource::make($taxRate)->toResponse($createTaxRateRequest)->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a tax rate
     *
     * @param string $legalEntityId
     * @param string $taxRateId
     * @param UpdateTaxRateRequest $updateTaxRateRequest
     * @return JsonResponse
     */
    public function update(string $legalEntityId, string $taxRateId, UpdateTaxRateRequest $updateTaxRateRequest): JsonResponse
    {
        $taxRateDTO = new GlobalTaxRateDTO($updateTaxRateRequest->allSafe());
        $taxRateDTO->legal_entity_id = $legalEntityId;
        $taxRate = $this->taxRateService->update($taxRateId, $taxRateDTO);

        return TaxRateResource::make($taxRate)->toResponse($updateTaxRateRequest);
    }

    /**
     * Delete a tax rate
     *
     * @param string $legalEntityId
     * @param string $taxRateId
     * @return JsonResponse
     */
    public function delete(string $legalEntityId, string $taxRateId): JsonResponse
    {
        $this->taxRateService->delete($legalEntityId, $taxRateId);

        return response()->json()->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    /**
     * Get the current tax rate for a legal entity
     *
     * @param string $legalEntityId
     * @return JsonResponse
     */
    public function currentRate(string $legalEntityId): JsonResponse
    {
        return response()->json(['tax_rate' => $this->taxRateService->getCurrentTaxRate($legalEntityId)]);
    }
}
