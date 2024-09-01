<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CompanyController extends Controller
{
    protected CompanyService $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    /**
     * Auto-suggest for companies
     *
     * Retrieves suggestions based on the provided value for a specific company.
     *
     * @OA\Get(
     *     path="/api/{company_id}/suggest/{value}",
     *     summary="Auto-suggest for companies",
     *     tags={"Companies"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="value",
     *         in="path",
     *         required=true,
     *         description="Search value for suggestion",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items()
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: Missing or invalid authentication token",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     *
     * @param string $companyId
     * @param string $value
     * @return JsonResponse
     */
    public function suggest(string $companyId, string $value): JsonResponse
    {
        $result = $this->companyService->suggest($value);

        return response()->json($result ?? []);
    }
}
