<?php

namespace App\Http\Controllers\Rent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rent\createCompanyRentRequest;
use App\Http\Requests\Rent\deleteCompanyRentRequest;
use App\Http\Requests\Rent\getCompanyRentRequest;
use App\Http\Requests\Rent\updateCompanyRentRequest;
use App\Http\Resources\Rent\CompanyRentResource;
use App\Enums\TablePreferenceType;
use App\Models\CompanyRent;
use App\Services\CompanyRentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CompanyRentController extends Controller
{

    protected string $model = CompanyRent::class;

    protected CompanyRentService $companyRentService;

    public function __construct(CompanyRentService $companyRentService)
    {
        $this->companyRentService = $companyRentService;
    }

    /**
     * Get all rents of a company
     *
     * @param string $companyId
     * @param getCompanyRentRequest $getCompanyRentRequest
     * @return JsonResponse
     */
    public function index(string $companyId, Request $getCompanyRentRequest): array
    {
        return parent::getAll($companyId, $getCompanyRentRequest, TablePreferenceType::company_rents()->getIndex());
    }

    /**
     * Get one rent of a company
     *
     * @param string $companyId
     * @param string $rentId
     * @param Request $request
     * @return JsonResponse
     */
    public function view(string $companyId, string $rentId, Request $request): JsonResponse
    {
        $loan = $this->companyRentService->view($rentId);

        return CompanyRentResource::make($loan)->toResponse($request);
    }

    /**
     * Create a enw rent
     *
     * @param string $companyId
     * @param createCompanyRentRequest $createCompanyRentRequest
     * @return JsonResponse
     */
    public function create(string $companyId, CreateCompanyRentRequest $createCompanyRentRequest): JsonResponse
    {
        $rentAttributes = $createCompanyRentRequest->validated();
        $rentAttributes['author_id'] = auth()->user()->id;
        $rentAttributes['admin_amount'] = convertLoanAmountToEuro($companyId, $rentAttributes['amount']);
        $rent = $this->companyRentService->create($rentAttributes);

        return CompanyRentResource::make($rent)
          ->toResponse($createCompanyRentRequest)
          ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a rent
     *
     * @param string $companyId
     * @param string $rentId
     * @param updateCompanyRentRequest $updateCompanyRentRequest
     * @return JsonResponse
     */
    public function update(string $companyId, string $rentId, UpdateCompanyRentRequest $updateCompanyRentRequest): JsonResponse
    {
        $rentAttributes = $updateCompanyRentRequest->validated();
        $rentAttributes['admin_amount'] = convertLoanAmountToEuro($companyId, $rentAttributes['amount']);
        $rent = $this->companyRentService->update($rentId, $rentAttributes);

        return CompanyRentResource::make($rent)->toResponse($updateCompanyRentRequest);
    }

    public function delete(string $companyId, string $rentId): JsonResponse
    {
        $this->companyRentService->delete($rentId);

        return response()->json()->setStatusCode(Response::HTTP_NO_CONTENT);
    }
}
