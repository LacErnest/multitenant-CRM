<?php

namespace App\Http\Controllers\Loan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Loan\CreateCompanyLoanRequest;
use App\Http\Requests\Loan\GetCompanyLoanRequest;
use App\Http\Requests\Loan\UpdateCompanyLoanRequest;
use App\Http\Resources\Loan\CompanyLoanResource;
use App\Services\CompanyLoanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CompanyLoanController extends Controller
{
    protected CompanyLoanService $companyLoanService;

    public function __construct(CompanyLoanService $companyLoanService)
    {
        $this->companyLoanService = $companyLoanService;
    }

    /**
     * List all loans for a company
     *
     * @param string $companyId
     * @param GetCompanyLoanRequest $getCompanyLoanRequest
     * @return JsonResponse
     */
    public function index(string $companyId, GetCompanyLoanRequest $getCompanyLoanRequest): JsonResponse
    {
        $amount = (integer)$getCompanyLoanRequest->input('amount', 10);
        $offset = (integer)$getCompanyLoanRequest->input('page', 0) * $amount;

        $loans = $this->companyLoanService->index($amount, $offset, 'issued_at', 'desc');
        $count = $this->companyLoanService->count();

        return response()->json(['count' => $count, 'data' => CompanyLoanResource::collection($loans)]);
    }

    /**
     * Get one loan from a company
     *
     * @param string $companyId
     * @param string $loanId
     * @param Request $request
     * @return JsonResponse
     */
    public function view(string $companyId, string $loanId, Request $request): JsonResponse
    {
        $loan = $this->companyLoanService->view($loanId);

        return CompanyLoanResource::make($loan)->toResponse($request);
    }

    /**
     * Create a new loan for a company
     *
     * @param string $companyId
     * @param CreateCompanyLoanRequest $createCompanyLoanRequest
     * @return JsonResponse
     */
    public function create(string $companyId, CreateCompanyLoanRequest $createCompanyLoanRequest): JsonResponse
    {
        $loanAttributes = $createCompanyLoanRequest->validated();
        $loanAttributes['author_id'] = auth()->user()->id;
        $loanAttributes['admin_amount'] = convertLoanAmountToEuro($companyId, $loanAttributes['amount']);
        $loanAttributes['amount_left'] = $loanAttributes['amount'];
        $loanAttributes['admin_amount_left'] = $loanAttributes['admin_amount'];
        $loan = $this->companyLoanService->create($loanAttributes);

        return CompanyLoanResource::make($loan)->toResponse($createCompanyLoanRequest)->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a loan from a company
     * @param string $companyId
     * @param string $loanId
     * @param UpdateCompanyLoanRequest $updateCompanyLoanRequest
     * @return JsonResponse
     */
    public function update(string $companyId, string $loanId, UpdateCompanyLoanRequest $updateCompanyLoanRequest): JsonResponse
    {
        $loanAttributes = $updateCompanyLoanRequest->validated();
        $loanAttributes['admin_amount'] = convertLoanAmountToEuro($companyId, $loanAttributes['amount']);
        $loan = $this->companyLoanService->update($loanId, $loanAttributes);

        return CompanyLoanResource::make($loan)->toResponse($updateCompanyLoanRequest);
    }

    /**
     * Delete a loan
     * @param string $companyId
     * @param string $loanId
     * @return JsonResponse
     */
    public function delete(string $companyId, string $loanId): JsonResponse
    {
        $this->companyLoanService->delete($loanId);

        return response()->json()->setStatusCode(Response::HTTP_NO_CONTENT);
    }
}
