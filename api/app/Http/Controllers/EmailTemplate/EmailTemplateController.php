<?php

namespace App\Http\Controllers\EmailTemplate;

use App\DTO\EmailTemplates\EmailTemplateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmailTemplate\EmailTemplateCreateRequest;
use App\Http\Requests\EmailTemplate\EmailTemplateUpdateRequest;
use App\Http\Resources\EmailTemplate\EmailTemplateResource;
use App\Services\EmailTemplateService;
use App\Services\EmailTemplatesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Response as HttpResponse;

class EmailTemplateController extends Controller
{
    /**
     * @var EmailTemplateService
     */
    protected EmailTemplateService $emailTemplateService;

    public function __construct(EmailTemplateService $emailTemplateService)
    {
        $this->emailTemplateService = $emailTemplateService;
    }

    /**
     * Get all email templates documents
     *
     *  @param string $companyId
     *  @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $emailTemplaes = $this->emailTemplateService->getAll();

        return EmailTemplateResource::collection($emailTemplaes)->toResponse($request);
    }

    /**
     * Get the document of a specifi email template
     *
     *  @param string $companyId
     *  @param Request $request
     * @return JsonResponse
     */
    public function view(string $companyId, $id, Request $request): JsonResponse
    {
        $emailTemplate = $this->emailTemplateService->findById($id);

        return EmailTemplateResource::make($emailTemplate)->toResponse($request);
    }

    /**
     * Create the document of a specific email template
     *
     * @param string $companyId
     * @param EmailTemplateCreateRequest $emailTemplateUpdateRequest
     * @return JsonResponse
     */
    public function create(EmailTemplateCreateRequest $emailTemplateUpdateRequest): JsonResponse
    {
        $emailTemplateDTO = new EmailTemplateDTO($emailTemplateUpdateRequest->allSafe());
        $emailTemplate = $this->emailTemplateService->create($emailTemplateDTO);

        return EmailTemplateResource::make($emailTemplate)->toResponse($emailTemplateUpdateRequest);
    }

    /**
     * Update the document of a given email template
     *
     * @param string $companyId
     * @param EmailTemplateUpdateRequest $emailTemplateUpdateRequest
     * @return JsonResponse
     */
    public function update(
        string $companyId,
        string $id,
        EmailTemplateUpdateRequest $emailTemplateUpdateRequest
    ): JsonResponse {
        $emailTemplateDTO = new EmailTemplateDTO($emailTemplateUpdateRequest->allSafe());
        $emailTemplate = $this->emailTemplateService->update($id, $emailTemplateDTO);

        return EmailTemplateResource::make($emailTemplate)->toResponse($emailTemplateUpdateRequest);
    }

    /**
     * Mark as given email template as default
     *
     * @param string $companyId
     * @param string $id
     * @return JsonResponse
     */
    public function markAsDefault(string $companyId, string $id, Request $response): JsonResponse
    {
        $setting = $this->emailTemplateService->markAsDefault($id);

        return EmailTemplateResource::make($setting)->toResponse($response);
    }

    /**
     * Toggle email templates globally disabled status
     *
     * @param string $companyId
     * @return JsonResponse
     */
    public function toggleGloballyDisabledStatus(string $companyId): JsonResponse
    {
        $status = $this->emailTemplateService->toggleGloballyDisabledStatus($companyId);

        return response()->json($status);
    }

    /**
     * Get email templates globally disabled status
     *
     * @param string $companyId
     * @return JsonResponse
     */
    public function getGloballyDisabledStatus(string $companyId): JsonResponse
    {
        $status = $this->emailTemplateService->getGloballyDisabledStatus($companyId);

        return response()->json($status);
    }

    /**
     * Delte the document of the email template
     *
     * @param string $companyId
     * @param string $id
     * @return JsonResponse
     */
    public function delete(
        string $companyId,
        string $id
    ): JsonResponse {
        $status = $this->emailTemplateService->delete($id);

        return response()->json(array('status' => $status));
    }
}
