<?php

namespace App\Http\Controllers\Commission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commission\ConfirmPaymentBySalespersonRequest;
use App\Http\Requests\Commission\GetCommissionDataRequest;
use App\Http\Requests\Commission\UpdateCommissionPaymentLogRequest;
use App\Http\Requests\Commission\CreateCommissionPaymentLogRequest;
use App\Http\Resources\Commission\CommissionPaymentLogResource;
use App\Services\CommissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommissionPaymentLogController extends Controller
{
    /**
     * @var CommissionService
     */
    protected CommissionService $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    /**
     * Get commission payment logs
     *
     * Retrieves commission payment logs based on the provided criteria.
     *
     * @OA\Get(
     *     path="/api/commissions/payment_log",
     *     summary="Get commission payment logs",
     *     tags={"Commissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of records per page",
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: Invalid or missing JWT token"
     *     )
     * )
     *
     * @param GetCommissionDataRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Exception
     */
    public function getPaymentLogs(GetCommissionDataRequest $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $paymentLogs = $this->commissionService->getPaymentLog($request->allSafe());
        JsonResource::$wrap = 'data';
        return CommissionPaymentLogResource::collection($paymentLogs);
    }

    /**
     * Create commission payment log
     *
     * Creates a new commission payment log based on the provided data.
     *
     * @OA\Post(
     *     path="/api/commissions/payment_log",
     *     summary="Create commission payment log",
     *     tags={"Commissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Success: Payment log created",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Payment log created")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: Invalid or missing JWT token"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity: Validation error"
     *     )
     * )
     *
     * @param CreateCommissionPaymentLogRequest $request
     * @return array
     */
    public function createCommissionPaymentLog(CreateCommissionPaymentLogRequest $request): array
    {
        $newCommissionPaymentLog = $this->commissionService->createCommissionPaymentLog($request->allSafe());
        return [
        'message' => ($newCommissionPaymentLog === null) ? 'Error' : 'Payment log created'
        ];
    }

    /**
     * Get total open commission amount
     *
     * Retrieves the total open commission amount based on the specified criteria.
     *
     * @OA\Get(
     *     path="/api/commissions/get_total_open_amount",
     *     summary="Get total open commission amount",
     *     tags={"Commissions"},
     *     @OA\Parameter(
     *         name="day",
     *         in="query",
     *         description="Day of the commission to filter",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="week",
     *         in="query",
     *         description="Week of the commission to filter",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         description="Month of the commission to filter",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="quarter",
     *         in="query",
     *         description="Quarter of the commission to filter",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Year of the commission to filter",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="sales_person_id",
     *         in="query",
     *         description="Salesperson ID to filter",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_commission_amount", type="integer", example=5000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: Invalid or missing JWT token"
     *     )
     * )
     *
     * @param GetCommissionDataRequest $request
     * @return array
     */
    public function getTotalOpenAmount(GetCommissionDataRequest $request): array
    {
        $day = $request->input('day', 0);
        $week = $request->input('week', 0);
        $month = $request->input('month', 0);
        $quarter = $request->input('quarter', 0);
        $year = $request->input('year', 0);
        $salespersonId = $request->input('sales_person_id', 0);
        $totalCommissionAmount = CommissionService::getCommissionTotalOpenAmount($salespersonId, $day, $week, $month, $quarter, $year);
        return [
        'total_commission_amount' => $totalCommissionAmount ?? 0
        ];
    }

    /**
     * Confirm payment by salesperson
     *
     * Marks a commission payment log as approved by the salesperson.
     *
     * @OA\Put(
     *     path="/api/commissions/confirm_payment/{paymentLogId}",
     *     summary="Confirm payment by salesperson",
     *     tags={"Commissions"},
     *     @OA\Parameter(
     *         name="paymentLogId",
     *         in="path",
     *         required=true,
     *         description="ID of the commission payment log to confirm",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Payment confirmed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment log not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: Invalid or missing JWT token"
     *     )
     * )
     *
     * @param int $paymentLogId
     * @param ConfirmPaymentBySalespersonRequest $request
     * @return array
     */
    public function confirmPayment($paymentLogId, ConfirmPaymentBySalespersonRequest $request): array
    {
        $confirmedPaymentLog = $this->commissionService->updateCommissionPaymentLog($paymentLogId, [
        'approved' => 1
        ]);

        return [
        'message' => ($confirmedPaymentLog === null) ? 'Error' : 'Payment confirmed'
        ];
    }
}
