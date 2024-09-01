<?php

namespace App\Http\Controllers\Analytics;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Exports\EarnOutSummaryExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\CommissionSummaryRequest;
use App\Http\Requests\Analytics\ApproveEarnOutRequest;
use App\Http\Requests\Analytics\ConfirmEarnOutRequest;
use App\Http\Requests\Analytics\GetEarnOutSummaryRequest;
use App\Http\Requests\GetEarnOutStatusRequest;
use App\Http\Resources\Analytics\EarnOutStatusResource;
use App\Models\Company;
use App\Services\AnalyticService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\UnauthorizedException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnalyticsController extends Controller
{
    protected AnalyticService $analyticService;

    private $entities = ['quotes', 'orders', 'invoices', 'purchase_orders'];

    public function __construct(AnalyticService $analyticService)
    {
        $this->analyticService = $analyticService;
    }

    /**
     * Get Analytic Counts
     *
     * Retrieves analytic counts for graphs and statistics based on specified time periods.
     *
     * @OA\Post(
     *     path="/api/dashboard/",
     *     summary="Get Analytic Counts",
     *     tags={"Analytics"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="day", type="integer", example=15, description="Day of the month (1-31)"),
     *             @OA\Property(property="week", type="integer", example=25, description="Week of the year (1-52)"),
     *             @OA\Property(property="month", type="integer", example=6, description="Month of the year (1-12)"),
     *             @OA\Property(property="quarter", type="integer", example=3, description="Quarter of the year (1-4)"),
     *             @OA\Property(property="year", type="integer", example=2024, description="Year"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="metric_name", type="integer", example=100, description="Value of the metric"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication token",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid input data",
     *     )
     * )
     *
     * @param Request $request
     * @return array
     * @throws UnauthorizedException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function get(Request $request)
    {
        if (UserRole::isAdmin(auth()->user()->role)) {
            $validatedData = $request->validate([
            'day' => 'integer|between:1,31',
            'week' => 'integer|between:1,52',
            'month' => 'integer|between:1,12',
            'quarter' => 'integer|between:1,4',
            'year' => 'integer|max:' . now()->year
            ]);

            $day = $request->input('day', 0);
            $week = $request->input('week', 0);
            $month = $request->input('month', 0);
            $quarter = $request->input('quarter', 0);
            $year = $request->input('year', 0);

            return $this->analyticService->get($day, $week, $month, $quarter, $year);
        }
        throw new UnauthorizedException();
    }

    /**
     * Overview of numbers for each analytic entity
     *
     * Retrieves summary numbers for the specified analytic entity based on provided time periods.
     *
     * @OA\Get(
     *     path="/api/dashboard/{entity}/summary",
     *     summary="Get Summary Numbers for Analytic Entity",
     *     tags={"Analytics"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="entity",
     *         in="path",
     *         required=true,
     *         description="Analytic entity name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="day",
     *         in="query",
     *         description="Day of the month (1-31)",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="week",
     *         in="query",
     *         description="Week of the year (1-52)",
     *         @OA\Schema(type="integer", example=25)
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Month of the year (1-12)",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="quarter",
     *         in="query",
     *         description="Quarter of the year (1-4)",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Year",
     *         @OA\Schema(type="integer", example=2024)
     *     ),
     *     @OA\Parameter(
     *         name="periods",
     *         in="query",
     *         description="Number of periods",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="metric_name", type="integer", example=100, description="Value of the metric"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication token",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Entity not recognized",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid input data",
     *     )
     * )
     *
     * @param string $entity
     * @param Request $request
     * @return array
     * @throws UnauthorizedException
     * @throws ModelNotFoundException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function summary($entity, Request $request)
    {
        if (UserRole::isAdmin(auth()->user()->role)) {
            if (in_array(strtolower($entity), $this->entities)) {
                $validatedData = $request->validate([
                'day' => 'integer|between:1,31',
                'week' => 'integer|between:1,52',
                'month' => 'integer|between:1,12',
                'quarter' => 'integer|between:1,4',
                'year' => 'integer|max:' . now()->year,
                'periods' => 'integer|between:0,4'
                ]);

                $day = $request->input('day', 0);
                $week = $request->input('week', 0);
                $month = $request->input('month', 0);
                $quarter = $request->input('quarter', 0);
                $year = $request->input('year', 0);
                $periods = $request->input('periods', 0);

                return $this->analyticService->summary($entity, $day, $week, $month, $quarter, $year, $periods);
            }
            throw new ModelNotFoundException();
        }
        throw new UnauthorizedException();
    }

    /**
     * Analytic counts for graphs and stats of one company
     *
     * Retrieves analytic counts for graphs and statistics of a specific company based on specified time periods.
     *
     * @OA\Get(
     *     path="/api/{company_id}/dashboard",
     *     summary="Get Analytic Counts for One Company",
     *     tags={"Analytics"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="ID of the company",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="day",
     *         in="query",
     *         description="Day of the month (1-31)",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="week",
     *         in="query",
     *         description="Week of the year (1-52)",
     *         @OA\Schema(type="integer", example=25)
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Month of the year (1-12)",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="quarter",
     *         in="query",
     *         description="Quarter of the year (1-4)",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Year",
     *         @OA\Schema(type="integer", example=2024)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="metric_name", type="integer", example=100, description="Value of the metric"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication token",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Company not found",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid input data",
     *     )
     * )
     *
     * @param int $company_id
     * @param Request $request
     * @return array
     * @throws UnauthorizedException
     * @throws ModelNotFoundException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function getCompany($company_id, Request $request)
    {
        $validatedData = $request->validate([
          'day' => 'integer|between:1,31',
          'week' => 'integer|between:1,52',
          'month' => 'integer|between:1,12',
          'quarter' => 'integer|between:1,4',
          'year' => 'integer|max:' . now()->year
        ]);

        $day = $request->input('day', 0);
        $week = $request->input('week', 0);
        $month = $request->input('month', 0);
        $quarter = $request->input('quarter', 0);
        $year = $request->input('year', 0);

        return $this->analyticService->getCompany($company_id, $day, $week, $month, $quarter, $year);
    }

    /**
     * Overview of numbers for each analytic of one company
     *
     * Retrieves summary data for analytics of a specific company based on the provided parameters.
     *
     * @OA\Get(
     *     path="/api/dashboard/{company_id}/{entity}/summary",
     *     summary="Get Summary for Analytics of a Company",
     *     tags={"Analytics"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="ID of the company",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="entity",
     *         in="path",
     *         required=true,
     *         description="Entity name for analytics",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="day",
     *         in="query",
     *         description="Day of the month (1-31)",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="week",
     *         in="query",
     *         description="Week of the year (1-52)",
     *         @OA\Schema(type="integer", example=25)
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Month of the year (1-12)",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="quarter",
     *         in="query",
     *         description="Quarter of the year (1-4)",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Year",
     *         @OA\Schema(type="integer", example=2024)
     *     ),
     *     @OA\Parameter(
     *         name="periods",
     *         in="query",
     *         description="Number of periods",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication token",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Entity not found",
     *     )
     * )
     *
     * @param string $company_id
     * @param string $entity
     * @param Request $request
     * @return array|mixed
     * @throws UnauthorizedException
     * @throws ModelNotFoundException
     */
    public function summaryCompany($company_id, $entity, Request $request)
    {
        if (in_array(strtolower($entity), $this->entities)) {
            $validatedData = $request->validate([
            'day' => 'integer|between:1,31',
            'week' => 'integer|between:1,52',
            'month' => 'integer|between:1,12',
            'quarter' => 'integer|between:1,4',
            'year' => 'integer|max:' . now()->year,
            'periods' => 'integer|between:0,4'
            ]);

            $day = $request->input('day', 0);
            $week = $request->input('week', 0);
            $month = $request->input('month', 0);
            $quarter = $request->input('quarter', 0);
            $year = $request->input('year', 0);
            $periods = $request->input('periods', 0);

            return $this->analyticService->summarycompany($entity, $day, $week, $month, $quarter, $year, $periods, $company_id);
        }
        throw new ModelNotFoundException();
    }

    /**
     * Earnout Summary
     *
     * Retrieves earnout summary for a specific company based on specified quarter and year.
     *
     * @OA\Get(
     *     path="/api/dashboard/{company_id}/earn_out_summary",
     *     summary="Get Earnout Summary for Company",
     *     tags={"Analytics"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="ID of the company",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="quarter",
     *         in="query",
     *         description="Quarter of the year (1-4)",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Year",
     *         @OA\Schema(type="integer", example=2024)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="metric_name", type="integer", example=100, description="Value of the metric"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication token",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Company not found",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid input data",
     *     )
     * )
     *
     * @param string $companyId
     * @param GetEarnOutSummaryRequest $getEarnOutSummaryRequest
     * @return array
     * @throws UnauthorizedException
     * @throws ModelNotFoundException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function earnoutSummary(string $companyId, GetEarnOutSummaryRequest $getEarnOutSummaryRequest): array
    {
        $quarter = $getEarnOutSummaryRequest->input('quarter', 0);
        $year = $getEarnOutSummaryRequest->input('year', 0);

        return $this->analyticService->earnoutSummary($companyId, $year, $quarter);
    }

    /**
     * Set Earn Out Status as Approved
     *
     * Sets the earn out status as approved for a specific company based on the provided parameters.
     *
     * @OA\Post(
     *     path="/api/dashboard/{company_id}/earn_out_status/approve",
     *     summary="Set Earn Out Status as Approved",
     *     tags={"Analytics"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="ID of the company",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Earn out status has been approved."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication token",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Company not found",
     *     )
     * )
     *
     * @param string $companyId
     * @param ApproveEarnOutRequest $approveEarnOutRequest
     * @return JsonResponse
     * @throws UnauthorizedException
     */
    public function setAsApproved(string $companyId, ApproveEarnOutRequest $approveEarnOutRequest): JsonResponse
    {
        $quarter = $approveEarnOutRequest->input('quarter', 0);
        $year = $approveEarnOutRequest->input('year', 0);
        $message = $this->analyticService->setAsApproved($companyId, auth()->user()->name, $year, $quarter);

        return response()->json(['message' => $message]);
    }

    /**
     * Set Earn Out Status as Confirmed
     *
     * Sets the earn out status as confirmed for a specific company based on the provided parameters.
     *
     * @OA\Patch(
     *     path="/api/dashboard/{company_id}/earn_out_status/confirm",
     *     summary="Set Earn Out Status as Confirmed",
     *     tags={"Analytics"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="ID of the company",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Earn out status has been confirmed."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication token",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Company not found",
     *     )
     * )
     *
     * @param string $companyId
     * @param ConfirmEarnOutRequest $confirmEarnOutRequest
     * @return JsonResponse
     * @throws UnauthorizedException
     */
    public function setAsConfirmed(string $companyId, ConfirmEarnOutRequest $confirmEarnOutRequest): JsonResponse
    {
        $quarter = $confirmEarnOutRequest->input('quarter', 0);
        $year = $confirmEarnOutRequest->input('year', 0);
        $message = $this->analyticService->setAsConfirmed($companyId, auth()->user()->name, $year, $quarter);

        return response()->json(['message' => $message]);
    }

    /**
     * Set Earn Out Status as Received
     *
     * Sets the earn out status as received for a specific company based on the provided parameters.
     *
     * @OA\Patch(
     *     path="/api/dashboard/{company_id}/earn_out_status/received",
     *     summary="Set Earn Out Status as Received",
     *     tags={"Analytics"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="ID of the company",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Earn out status has been set as received."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication token",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Company not found",
     *     )
     * )
     *
     * @param string $companyId
     * @param ApproveEarnOutRequest $approveEarnOutRequest
     * @return JsonResponse
     * @throws UnauthorizedException
     */
    public function setAsReceived(string $companyId, ApproveEarnOutRequest $approveEarnOutRequest): JsonResponse
    {
        $quarter = $approveEarnOutRequest->input('quarter', 0);
        $year = $approveEarnOutRequest->input('year', 0);
        $message = $this->analyticService->setAsReceived($year, $quarter);

        return response()->json(['message' => $message]);
    }

    /**
     * Get Earn Out Status
     *
     * Retrieves earn out status data for a specific company based on the provided or current quarter and year.
     *
     * @OA\Get(
     *     path="/api/dashboard/{company_id}/earn_out_status",
     *     summary="Get Earn Out Status for Company",
     *     tags={"Analytics"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="ID of the company",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="quarter",
     *         in="query",
     *         description="Quarter of the year (1-4)",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Year",
     *         @OA\Schema(type="integer", example=2024)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="No Content - No earn out status found for the company"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication token",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Company not found",
     *     )
     * )
     *
     * @param string $companyId
     * @param GetEarnOutStatusRequest $getEarnOutStatusRequest
     * @return JsonResponse
     * @throws UnauthorizedException
     * @throws ModelNotFoundException
     */
    public function getStatus(string $companyId, GetEarnOutStatusRequest $getEarnOutStatusRequest): JsonResponse
    {
        $month = date('n');
        $quarter = $getEarnOutStatusRequest->input('quarter', ceil($month / 3));
        $year = $getEarnOutStatusRequest->input('year', now()->year);
        $status = $this->analyticService->getStatus($year, $quarter);

        if ($status) {
            return EarnOutStatusResource::make($status)->toResponse($getEarnOutStatusRequest);
        }

        return response()->json()->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    /**
     * Commission Summary
     *
     * Retrieves commission summary based on specified time periods and salesperson ID.
     *
     * @OA\Get(
     *     path="/api/dashboard/commission-summary",
     *     summary="Get Commission Summary",
     *     tags={"Analytics"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="day",
     *         in="query",
     *         description="Day of the month (1-31)",
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Parameter(
     *         name="week",
     *         in="query",
     *         description="Week of the year (1-52)",
     *         @OA\Schema(type="integer", example=25)
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Month of the year (1-12)",
     *         @OA\Schema(type="integer", example=6)
     *     ),
     *     @OA\Parameter(
     *         name="quarter",
     *         in="query",
     *         description="Quarter of the year (1-4)",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Year",
     *         @OA\Schema(type="integer", example=2024)
     *     ),
     *     @OA\Parameter(
     *         name="sales_person_id",
     *         in="query",
     *         description="ID of the salesperson",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="metric_name", type="integer", example=100, description="Value of the metric"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication token",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid input data",
     *     )
     * )
     *
     * @param CommissionSummaryRequest $commissionSummaryRequest
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     */
    public function commissionSummary(CommissionSummaryRequest $commissionSummaryRequest)
    {
        $day = $commissionSummaryRequest->input('day', 0);
        $week = $commissionSummaryRequest->input('week', 0);
        $month = $commissionSummaryRequest->input('month', 0);
        $quarter = $commissionSummaryRequest->input('quarter', 0);
        $year = $commissionSummaryRequest->input('year', 0);
        $salespersonId = $commissionSummaryRequest->input('sales_person_id', 0);

        return $this->analyticService->commissionSummary($salespersonId, $day, $week, $month, $quarter, $year);
    }

    /**
     * Export Earn Out Summary
     *
     * Exports earn out summary data for a specific company based on the provided or current quarter and year.
     *
     * @OA\Get(
     *     path="/api/dashboard/{company_id}/earn_out_summary/export",
     *     summary="Export Earn Out Summary for Company",
     *     tags={"Analytics"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="ID of the company",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="quarter",
     *         in="query",
     *         description="Quarter of the year (1-4)",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Year",
     *         @OA\Schema(type="integer", example=2024)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation. Returns an Excel file.",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     *             @OA\Schema(type="file")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication token",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Company not found",
     *     )
     * )
     *
     * @param string $companyId
     * @param GetEarnOutSummaryRequest $getEarnOutSummaryRequest
     * @return BinaryFileResponse
     * @throws UnauthorizedException
     * @throws ModelNotFoundException
     */
    public function exportSummary(string $companyId, GetEarnOutSummaryRequest $getEarnOutSummaryRequest): BinaryFileResponse
    {
        $quarter = $getEarnOutSummaryRequest->input('quarter', 0);
        $year = $getEarnOutSummaryRequest->input('year');

        if ($quarter == 0) {
            $name = $year;
        } else {
            $name = 'Q' . $quarter . '_' . $year;
        }

        if (UserRole::isAdmin(auth()->user()->role) || Company::find($companyId)->currency_code == CurrencyCode::EUR()->getIndex()) {
            $euro = true;
        } else {
            $euro = false;
        }

        $data = $this->earnoutSummary($companyId, $getEarnOutSummaryRequest);
        $export = new EarnOutSummaryExport($data, $euro);

        return Excel::download($export, 'EarnOut_' . $name . '.xlsx');
    }

    /**
     * Earn Out Prospection
     *
     * Retrieves earn out prospection data for a specific company based on the current quarter and year.
     *
     * @OA\Get(
     *     path="/api/dashboard/{company_id}/earn_out_prospection",
     *     summary="Get Earn Out Prospection for Company",
     *     tags={"Analytics"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="ID of the company",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="metric_name", type="integer", example=100, description="Value of the metric"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing authentication token",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found - Company not found",
     *     )
     * )
     *
     * @param string $companyId
     * @return array
     * @throws UnauthorizedException
     * @throws ModelNotFoundException
     */
    public function earnOutProspection(string $companyId): array
    {
        $month = date('n');
        $quarter = ceil($month / 3);
        $year = now()->year;

        return $this->analyticService->earnOutProspection($companyId, $year, $quarter);
    }
}
