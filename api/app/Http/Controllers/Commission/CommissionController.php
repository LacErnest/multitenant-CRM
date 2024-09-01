<?php

namespace App\Http\Controllers\Commission;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commission\IndividualCommissionPaymentFormRequest;
use App\Services\CommissionService;
use App\Http\Requests\Commission\CommissionPercentageRequest;
use Illuminate\Http\JsonResponse;

class CommissionController extends Controller
{
    protected CommissionService $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    /**
     * Create commission percentage
     *
     * Creates a new commission percentage for a specific order, invoice, and salesperson.
     *
     * @OA\Post(
     *     path="/api/commissions/percentage/{orderId}/{invoiceId}/{salesPersonId}",
     *     summary="Create commission percentage",
     *     tags={"Commissions"},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         description="ID of the order",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="invoiceId",
     *         in="path",
     *         required=true,
     *         description="ID of the invoice",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="salesPersonId",
     *         in="path",
     *         required=true,
     *         description="ID of the salesperson",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Operation successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order or invoice not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error: Invalid request parameters"
     *     )
     * )
     *
     * @param int $orderId
     * @param int $invoiceId
     * @param int $salesPersonId
     * @param CommissionPercentageRequest $request
     * @return JsonResponse
     */
    public function createCommissionPercentage($orderId, $invoiceId, $salesPersonId, CommissionPercentageRequest $request): JsonResponse
    {
        $result = $this->commissionService->createCommissionPercentage($orderId, $invoiceId, $salesPersonId, $request->allSafe());

        return response()->json(['message' => ($result === null) ? 'Error' : 'Operation successful']);
    }

    /**
     * Update commission percentage
     *
     * Update an existing commission percentage for a specific order, invoice, and salesperson.
     *
     * @OA\Put(
     *     path="/api/commissions/percentage/{orderId}/{invoiceId}/{salesPersonId}",
     *     summary="Update commission percentage",
     *     tags={"Commissions"},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         description="ID of the order",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="invoiceId",
     *         in="path",
     *         required=true,
     *         description="ID of the invoice",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="salesPersonId",
     *         in="path",
     *         required=true,
     *         description="ID of the salesperson",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Operation successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order or invoice not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error: Invalid request parameters"
     *     )
     * )
     *
     * @param int $orderId
     * @param int $invoiceId
     * @param int $salesPersonId
     * @param CommissionPercentageRequest $request
     * @return JsonResponse
     */
    public function updateCommissionPercentage($orderId, $invoiceId, $salesPersonId, CommissionPercentageRequest $request): JsonResponse
    {
        $result = $this->commissionService->updateCommissionPercentage($orderId, $invoiceId, $salesPersonId, $request->allSafe());

        return response()->json(['message' => empty($result) ? 'Error' : 'Operation successful']);
    }

    /**
     * Remove commission for a given sales person
     *
     * Deletes the commission percentage for a specific order and salesperson.
     *
     * @OA\Delete(
     *     path="/api/commissions/percentage/{orderId}/{invoiceId}/{salesPersonId}",
     *     summary="Remove commission percentage",
     *     tags={"Commissions"},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         description="ID of the order",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="invoiceId",
     *         in="path",
     *         required=true,
     *         description="ID of the invoice",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="salesPersonId",
     *         in="path",
     *         required=true,
     *         description="ID of the salesperson",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Operation successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Order or salesperson not found"
     *     )
     * )
     *
     * @param string $orderId
     * @param string $invoiceId
     * @param string $salesPersonId
     * @return JsonResponse
     */
    public function deleteCommissionPercentage(string $orderId, string $invoiceId, string $salesPersonId): JsonResponse
    {
        $result = $this->commissionService->deleteCommissionPercentage($orderId, $invoiceId, $salesPersonId);

        return response()->json(['message' => ($result === null) ? 'Error' : 'Operation successful']);
    }

    /**
     * Remove commission percentage by ID
     *
     * Deletes the commission percentage by its unique ID.
     *
     * @OA\Delete(
     *     path="/api/commissions/percentage/{percentageId}",
     *     summary="Remove commission percentage by ID",
     *     tags={"Commissions"},
     *     @OA\Parameter(
     *         name="percentageId",
     *         in="path",
     *         required=true,
     *         description="ID of the commission percentage to delete",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Operation successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Commission percentage not found"
     *     )
     * )
     *
     * @param string $percentageId
     * @return JsonResponse
     */
    public function deleteCommissionPercentageById(string $percentageId): JsonResponse
    {
        $result = $this->commissionService->deleteCommissionPercentageById($percentageId);

        return response()->json(['message' => ($result === null) ? 'Error' : 'Operation successful']);
    }

    /**
     * Create individual commission payment
     *
     * Creates a new individual commission payment based on the provided data.
     *
     * @OA\Post(
     *     path="/api/commissions/individual_commission_payment",
     *     summary="Create individual commission payment",
     *     tags={"Commissions"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Operation successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or failed operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error")
     *         )
     *     )
     * )
     *
     * @param IndividualCommissionPaymentFormRequest $request
     * @return JsonResponse
     */
    public function createIndividualCommissionPayment(IndividualCommissionPaymentFormRequest $request): JsonResponse
    {
        $result = $this->commissionService->createIndividualCommissionPaymentFormRequest($request->validated());

        return response()->json(['message' => ($result === null) ? 'Error' : 'Operation successful']);
    }

    /**
     * Cancel individual commission payment
     *
     * Cancels an existing individual commission payment based on the provided identifiers.
     *
     * @OA\Delete(
     *     path="/api/commissions/individual_commission_payment/{orderId}/{invoiceId}/{salesPersonId}",
     *     summary="Cancel individual commission payment",
     *     tags={"Commissions"},
     *     @OA\Parameter(
     *         name="orderId",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="invoiceId",
     *         in="path",
     *         required=true,
     *         description="Invoice ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="salesPersonId",
     *         in="path",
     *         required=true,
     *         description="Salesperson ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Operation successful")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or failed operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error")
     *         )
     *     )
     * )
     *
     * @param string $orderId
     * @param string $invoiceId
     * @param string $salesPersonId
     * @return JsonResponse
     */
    public function cancelIndividualCommissionPayment(string $orderId, string $invoiceId, string $salesPersonId): JsonResponse
    {
        $result = $this->commissionService->cancelIndividualCommissionPaymentFormRequest($orderId, $invoiceId, $salesPersonId);

        return response()->json(['message' => ($result === null) ? 'Error' : 'Operation successful']);
    }
}
