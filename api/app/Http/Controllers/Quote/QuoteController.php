<?php

namespace App\Http\Controllers\Quote;

use App\DTO\Quotes\CreateProjectQuoteDTO;
use App\DTO\Quotes\CreateQuoteDTO;
use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\DownPaymentAmountType;
use App\Enums\TablePreferenceType;
use App\Enums\UserRole;
use App\Exports\DataTablesExport;
use App\Http\Requests\Export\ExportGetRequest;
use App\Http\Requests\Export\ExportQuoteRequest;
use App\Http\Requests\Item\ItemDeleteRequest;
use App\Http\Requests\Quote\CreateOrderFromQuoteRequest;
use App\Http\Requests\Quote\QuoteAddDocumentRequest;
use App\Http\Requests\Quote\QuoteCloneRequest;
use App\Http\Requests\Quote\QuoteCreateRequest;
use App\Http\Requests\Quote\QuoteDeleteDocumentRequest;
use App\Http\Requests\Quote\QuoteGetRequest;
use App\Http\Requests\Quote\QuoteUpdateRequest;
use App\Http\Requests\Quote\QuoteStatusUpdateRequest;
use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Item\ModifierResource;
use App\Models\Order;
use App\Models\TablePreference;
use App\Models\User;
use App\Services\Export\QuoteExporter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Item\ItemCreateRequest;
use App\Http\Requests\Item\ItemUpdateRequest;
use App\Http\Requests\PriceModifier\PriceModifierCreateRequest;
use App\Http\Requests\Quote\QuoteCreateForProjectRequest;
use App\Http\Resources\Order\OrderResource;
use App\Http\Resources\Quote\QuoteResource;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Setting;
use App\Services\ItemService;
use App\Services\QuoteService;
use App\Services\TablePreferenceService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Illuminate\Validation\UnauthorizedException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class QuoteController extends Controller
{
    protected string $model = Quote::class;

    protected QuoteService $quoteService;

    public function __construct(Itemservice $itemService, QuoteService $quoteService)
    {
        parent::__construct($itemService);
        $this->quoteService = $quoteService;
    }

    /**
     * Get all quotes of a specific company
     * @param string $companyId
     * @param QuoteGetRequest $request
     * @param null $entity
     * @return array
     */
    public function all(string $companyId, QuoteGetRequest $request, $entity = null): array
    {
        return parent::getAll($companyId, $request, TablePreferenceType::quotes()->getIndex());
    }

    /**
     * Get one quote of a specific company
     *
     * Retrieves details of a specific quote identified by quote ID within a project under a company.
     *
     * @OA\Get(
     *     path="/api/{company_id}/projects/{project_id}/quotes/{quote_id}",
     *     summary="Get one quote of a specific company",
     *     tags={"Quotes"},
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
     *         name="project_id",
     *         in="path",
     *         required=true,
     *         description="Project ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="quote_id",
     *         in="path",
     *         required=true,
     *         description="Quote ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User is not authorized to access the quote",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found: Quote not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Quote not found")
     *         )
     *     )
     * )
     *
     * @param string $company_id
     * @param string $project_id
     * @param string $quote_id
     * @param QuoteGetRequest $request
     * @return JsonResponse
     * @throws UnauthorizedException
     * @throws ModelNotFoundException
     */
    public function singleFromProject($company_id, $project_id, $quote_id, QuoteGetRequest $request)
    {
        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $project = Project::findOrFail($project_id);
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        if (UserRole::isSales(auth()->user()->role)) {
            $project = Project::findOrFail($project_id);
            $salesIds = User::where('email', auth()->user()->email)->pluck('id')->toArray();
            $isSalesPerson = Project::whereHas('salesPersons', function ($query) use ($salesIds) {
                $query->whereIn('user_id', $salesIds);
            })->exists();
            $isLeadGen = Project::whereHas('leadGens', function ($query) use ($salesIds) {
                $query->whereIn('user_id', $salesIds);
            })->exists();
            if (!$isSalesPerson && !$isLeadGen) {
                throw new UnauthorizedException();
            }
        }

        return parent::getSingleFromProject($company_id, $project_id, $quote_id);
    }

    /**
     * Create a new quote for a project
     *
     * Creates a new quote for a specific project under a company.
     *
     * @OA\Post(
     *     path="/api/{company_id}/projects/{project_id}/quotes",
     *     summary="Create a new quote for a project",
     *     tags={"Quotes"},
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
     *         name="project_id",
     *         in="path",
     *         required=true,
     *         description="Project ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Successful creation",
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request: Invalid input data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation error")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User is not authorized to create a quote",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     *
     * @param string $company_id
     * @param string $project_id
     * @param QuoteCreateForProjectRequest $quoteCreateForProjectRequest
     * @return JsonResponse
     * @throws UnauthorizedException
     */
    public function createQuoteForProject(
        string $companyId,
        string $projectId,
        QuoteCreateForProjectRequest $quoteCreateForProjectRequest
    ): JsonResponse {
        if (UserRole::isSales(auth()->user()->role)) {
            $project = Project::findOrFail($projectId);
            $salesIds = User::where('email', auth()->user()->email)->pluck('id')->toArray();
            $isSalesPerson = $project->salesPersons()->whereIn('user_id', $salesIds)->exists();
            $isLeadGen = $project->leadGens()->whereIn('user_id', $salesIds)->exists();
            if (!$isSalesPerson && !$isLeadGen) {
                throw new UnauthorizedException();
            }
        }
        $input = $quoteCreateForProjectRequest->allSafe();
        $quoteDTO = new CreateProjectQuoteDTO($input);
        $quote = $this->quoteService->createForProject($projectId, $quoteDTO);

        return QuoteResource::make($quote)->toResponse($quoteCreateForProjectRequest)->setStatusCode(Response::HTTP_CREATED);
    }

    public function createQuote(string $companyId, QuoteCreateRequest $quoteCreateRequest): JsonResponse
    {
        $input = $quoteCreateRequest->validated();
        $quoteDTO = new CreateQuoteDTO($input);
        $quote = $this->quoteService->create($quoteDTO);

        return QuoteResource::make($quote)->toResponse($quoteCreateRequest)->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update a quote for a project
     * @param $company_id
     * @param $project_id
     * @param $quote_id
     * @param QuoteUpdateRequest $request
     * @return QuoteResource|JsonResponse
     */
    public function updateQuote($company_id, $project_id, $quote_id, QuoteUpdateRequest $request)
    {
        if (UserRole::isSales(auth()->user()->role)) {
            $project = Project::findOrFail($project_id);
            $salesIds = User::where('email', auth()->user()->email)->pluck('id')->toArray();
            $isSalesPerson = $project->salesPersons()->whereIn('user_id', $salesIds)->exists();
            $isLeadGen = $project->leadGens()->whereIn('user_id', $salesIds)->exists();
            if (!$isSalesPerson && !$isLeadGen) {
                throw new UnauthorizedException();
            }
        }

        $quote = Quote::findOrFail($quote_id);
        if ($quote->project_id === $project_id) {
            $quote = $this->quoteService->update($quote, $request);
            return QuoteResource::make($quote);
        }
        throw new ModelNotFoundException();
    }

    /**
     * @param $company_id
     * @param $project_id
     * @param $quote_id
     * @param QuoteStatusUpdateRequest $request
     * @return QuoteResource
     */
    public function updateQuoteStatus($company_id, $project_id, $quote_id, QuoteStatusUpdateRequest $request)
    {
        if (UserRole::isSales(auth()->user()->role)) {
            $project = Project::findOrFail($project_id);
            $salesIds = User::where('email', auth()->user()->email)->pluck('id')->toArray();
            $isSalesPerson = $project->salesPersons()->whereIn('user_id', $salesIds)->exists();
            $isLeadGen = $project->leadGens()->whereIn('user_id', $salesIds)->exists();
            if (!$isSalesPerson && !$isLeadGen) {
                throw new UnauthorizedException();
            }
        }

        $quote = Quote::findOrFail($quote_id);
        if ($quote->project_id === $project_id) {
            $quote = $this->quoteService->update($quote, $request);
            return QuoteResource::make($quote);
        }
        throw new ModelNotFoundException();
    }

    /**
     * Create a new quote item
     *
     * Creates a new item for a specific quote under a project in a company.
     *
     * @OA\Post(
     *     path="/api/{company_id}/projects/{project_id}/quotes/{quote_id}/items",
     *     summary="Create a new quote item",
     *     tags={"Quotes"},
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
     *         name="project_id",
     *         in="path",
     *         required=true,
     *         description="Project ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="quote_id",
     *         in="path",
     *         required=true,
     *         description="Quote ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Item data",
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful creation",
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request: Invalid input data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation error")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User is not authorized to create items for the quote",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity: Quote is cancelled",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Quote is cancelled. No create, update, delete of items, price modifiers allowed.")
     *         )
     *     )
     * )
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $quoteId
     * @param ItemCreateRequest $request
     * @return ItemResource
     * @throws UnprocessableEntityHttpException
     */
    public function createItem(
        string $companyId,
        string $projectId,
        string $quoteId,
        ItemCreateRequest $request
    ): ItemResource {
        $this->quoteService->checkAuthorization($projectId);
        $this->quoteService->checkProjectDelivered($projectId);
        if (checkCancelledStatus(Quote::class, $quoteId)) {
            throw new UnprocessableEntityHttpException(
                'Quote is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::createItem($companyId, $projectId, $quoteId, $request);
    }

    /**
     * update quote item
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $quoteId
     * @param string $itemId
     * @param ItemUpdateRequest $request
     * @return ItemResource
     */
    public function updateItem(
        string $companyId,
        string $projectId,
        string $quoteId,
        string $itemId,
        ItemUpdateRequest $request
    ): ItemResource {
        $this->quoteService->checkAuthorization($projectId);
        $this->quoteService->checkProjectDelivered($projectId);
        if (checkCancelledStatus(Quote::class, $quoteId)) {
            throw new UnprocessableEntityHttpException(
                'Quote is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::updateItem($companyId, $projectId, $quoteId, $itemId, $request);
    }

    /**
     * delete quote item
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $quoteId
     * @param ItemDeleteRequest $request
     * @return Response
     */
    public function deleteItems(
        string $companyId,
        string $projectId,
        string $quoteId,
        ItemDeleteRequest $request
    ): Response {
        $this->quoteService->checkAuthorization($projectId);
        $this->quoteService->checkProjectDelivered($projectId);
        if (checkCancelledStatus(Quote::class, $quoteId)) {
            throw new UnprocessableEntityHttpException(
                'Quote is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::deleteItems($companyId, $projectId, $quoteId, $request);
    }

    /**
     * Create a new price modifier for a quote
     *
     * Creates a new price modifier for a specific quote under a project in a company.
     *
     * @OA\Post(
     *     path="/api/{company_id}/projects/{project_id}/quotes/{quote_id}/price_modifiers",
     *     summary="Create a new price modifier for a quote",
     *     tags={"Quotes"},
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
     *         name="project_id",
     *         in="path",
     *         required=true,
     *         description="Project ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="quote_id",
     *         in="path",
     *         required=true,
     *         description="Quote ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Price modifier data",
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful creation",
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request: Invalid input data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation error")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User is not authorized to create price modifiers for the quote",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity: Quote is cancelled",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Quote is cancelled. No create, update, delete of items, price modifiers allowed.")
     *         )
     *     )
     * )
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $quoteId
     * @param PriceModifierCreateRequest $request
     * @return ModifierResource
     * @throws UnprocessableEntityHttpException
     */
    public function createPriceModifier(
        string $companyId,
        string $projectId,
        string $quoteId,
        PriceModifierCreateRequest $request
    ): ModifierResource {
        $this->quoteService->checkAuthorization($projectId);
        $this->quoteService->checkProjectDelivered($projectId);
        if (checkCancelledStatus(Quote::class, $quoteId)) {
            throw new UnprocessableEntityHttpException(
                'Quote is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::createPriceModifier($companyId, $projectId, $quoteId, $request);
    }

    /**
     * Update a price modifier for a quote
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $quoteId
     * @param string $priceModifierId
     * @param PriceModifierCreateRequest $request
     * @return ModifierResource
     */
    public function updatePriceModifier(
        string $companyId,
        string $projectId,
        string $quoteId,
        string $priceModifierId,
        PriceModifierCreateRequest $request
    ): ModifierResource {
        $this->quoteService->checkAuthorization($projectId);
        $this->quoteService->checkProjectDelivered($projectId);
        if (checkCancelledStatus(Quote::class, $quoteId)) {
            throw new UnprocessableEntityHttpException(
                'Quote is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::updatePriceModifier($companyId, $projectId, $quoteId, $priceModifierId, $request);
    }

    /**
     * Delete a price modifier for a quote
     *
     * @param string $companyId
     * @param string $projectId
     * @param string $quoteId
     * @param string $priceModifierId
     * @return Response
     */
    public function deletePriceModifier(
        string $companyId,
        string $projectId,
        string $quoteId,
        string $priceModifierId
    ): Response {
        $this->quoteService->checkAuthorization($projectId);
        $this->quoteService->checkProjectDelivered($projectId);
        if (checkCancelledStatus(Quote::class, $quoteId)) {
            throw new UnprocessableEntityHttpException(
                'Quote is cancelled. No create, update, delete of items, price modifiers allowed.'
            );
        }

        return parent::deletePriceModifier($companyId, $projectId, $quoteId, $priceModifierId);
    }

    /**
     * Clone a quote
     * @param $company_id
     * @param $project_id
     * @param $quote_id
     * @param QuoteCloneRequest $request
     * @return mixed
     */
    public function clone($company_id, $project_id, $quote_id, QuoteCloneRequest $request)
    {

        if (!$quote = Quote::find($quote_id)) {
            return response()->json(['message' => 'cannot be cloned'], 400);
        }

        $destination_id = $request->input('destination_id', null);
        if ($destination_id === null) {
            $project = Project::with('contact')->findOrFail($project_id);
            $contactIds = $project->contact->customer->contacts()->get()->pluck('id')->toArray();
            $amountOfProjectsForCustomer = Project::whereIn('contact_id', $contactIds)->count() + 1;

            $newProject = Project::create([
              'name' => $project->contact->customer->name.'_'.$amountOfProjectsForCustomer,
              'contact_id' => $project->contact->id,
            ]);
            $newProject->salesPersons()->attach($project->salesPersons);
            $newProject->leadGens()->attach($project->leadGens);
            $destination_id = $newProject->id;
        } elseif ($destination_id == 'current') {
            $destination_id = $project_id;
        } else {
            $destinationOrder = Order::find($destination_id);

            if (!$destinationOrder) {
                return response()->json(['message' => 'Could not find requested order.'], 404);
            } else {
                $hasInvoicedOrder = OrderStatus::isInvoiced($destinationOrder->status);
                $hasDeliveredOrder = OrderStatus::isDelivered($destinationOrder->status);
                $hasDraftOrder = OrderStatus::isDraft($destinationOrder->status);

                if ($hasInvoicedOrder) {
                    return response()->json(['message' => 'Selected order has already been invoiced.'], 422);
                } elseif ($hasDeliveredOrder) {
                    return response()->json(['message' => 'Selected order has been delivered.'], 422);
                } elseif ($hasDraftOrder) {
                    return response()->json(['message' => 'Selected order has status draft.'], 422);
                }

                $destination_id = $destinationOrder->project_id;
            }
        }

        $format = getSettingsFormat($quote->legal_entity_id);
        $quoteNumber = transformFormat($format->quote_number_format, $format->quote_number + 1);
        $format->quote_number += 1;
        $format->save();
        return parent::cloneEntity($company_id, $project_id, $quote_id, $quoteNumber, $destination_id, Quote::class);
    }

    /**
     * Export data from quote
     * @param ExportGetRequest $request
     * @param $company_id
     * @param $project_id
     * @param $quote_id
     * @param $template_id
     * @param $format
     */

    public function export(ExportQuoteRequest $request, $company_id, $project_id, $quote_id, $template_id, $format)
    {
        if (!$project = Project::find($project_id)) {
            throw new ModelNotFoundException();
        }

        if (!$quote = $project->quotes()->find($quote_id)) {
            throw new ModelNotFoundException();
        }

        return (new QuoteExporter())->export($quote, $template_id, $format);
    }

    public function addDocument(
        QuoteAddDocumentRequest $addDocumentRequest,
        string $companyId,
        string $projectId,
        string $quoteId
    ) {
        $file = $addDocumentRequest->input('file');
        $this->quoteService->uploadDocument($quoteId, $file);

        return response()->json()->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    public function deleteDocument(
        QuoteDeleteDocumentRequest $deleteDocumentRequest,
        string $companyId,
        string $projectId,
        string $quoteId
    ) {
        $this->quoteService->deleteDocument($quoteId);

        return response()->json()->setStatusCode(Response::HTTP_NO_CONTENT);
    }
    /**
     * Export all quotes to excel
     *
     * @param string $companyId
     * @param QuoteGetRequest $request
     * @return BinaryFileResponse
     */
    public function exportDataTable(string $companyId, QuoteGetRequest $request): BinaryFileResponse
    {
        $amount = Quote::count();
        $request->merge([
          'amount' => $amount,
          'page' => 0,
        ]);

        $tablePreferenceType = TablePreferenceType::quotes();

        if (!empty($request->project)) {
            $tablePreferenceType = TablePreferenceType::project_quotes();
        }

        $tablePrefService = App::make(TablePreferenceService::class);
        $tablePrefs = $tablePrefService->getPreferences('users', $tablePreferenceType->getIndex())->getData();
        $columns = $tablePrefs->columns;
        $quotes = parent::getAll($companyId, $request, TablePreferenceType::quotes()->getIndex());
        $export = new DataTablesExport($quotes['data'], $columns);

        return Excel::download($export, 'quotes_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Get all quotes belonging to a project
     *
     * Retrieves all quotes associated with a specific project identified by project ID.
     *
     * @OA\Get(
     *     path="/api/{company_id}/projects/{project_id}/quotes",
     *     summary="Get all quotes belonging to a project",
     *     tags={"Quotes"},
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
     *         name="project_id",
     *         in="path",
     *         required=true,
     *         description="Project ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User is not authorized to access quotes for this project",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     *
     * @param string $companyId
     * @param string $projectId
     * @param Request $request
     * @param null $entity
     * @return array
     * @throws UnauthorizedException
     */
    public function getAllFromProject(string $companyId, string $projectId, Request $request, $entity = null): array
    {
        $request['project'] = $projectId;

        if (UserRole::isPm_restricted(auth()->user()->role)) {
            $project = Project::findOrFail($projectId);
            if ($project->project_manager_id != auth()->user()->id) {
                throw new UnauthorizedException();
            }
        }

        return parent::getAll($companyId, $request, TablePreferenceType::project_quotes()->getIndex());
    }
}
