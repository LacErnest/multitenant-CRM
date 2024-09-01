<?php

namespace App\Http\Controllers\Templates;


use App\Http\Controllers\Controller;
use App\Http\Requests\Preferences\TablePreferenceRequest;
use App\Http\Requests\Templates\TemplateAllRequest;
use App\Http\Requests\Templates\TemplateCreateRequest;
use App\Http\Requests\Templates\TemplateDeleteRequest;
use App\Http\Requests\Templates\TemplateGetRequest;
use App\Http\Requests\Templates\TemplateUpdateRequest;
use App\Http\Resources\Template\TemplateResource;
use App\Services\TemplateService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TemplateController extends Controller
{

    protected TemplateService $templateService;

    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Get company templates categories of a specific datatable for the logged in user
     *
     * @param $company_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function allCategories($company_id)
    {
        $templates = $this->templateService->getAllTemplateCategories();
        return response()->json(TemplateResource::collection($templates->sortBy('created_at')));
    }

    /**
     * Create company templates categories for a specific datatable for the logged in user
     *
     * @param $company_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function createCategory(TemplateCreateRequest $request, $company_id)
    {
        return $this->templateService->addTemplateCategory($request);
    }

    /**
     * Update company templates categories for a specific datatable for the logged in user
     *
     * @param $company_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCategory(TemplateCreateRequest $request, $company_id, $templateId)
    {
        return $this->templateService->updateTemplateCategory($request, $templateId);
    }

    /**
     * delete company templates categories for a specific datatable for the logged in user
     *
     * @param $company_id
     */
    public function deleteCategory(TemplateDeleteRequest $request, $company_id, $templateId)
    {
        $this->templateService->deleteTemplateCategory($templateId);
        return response()->json()->setStatusCode(Response::HTTP_NO_CONTENT);
    }

    /**
     * Get company template document of a specific datatable for the logged in user
     *
     * @param Request $request
     * @param string $company_id
     * @param string $company_id
     * @param string $template_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function view(Request $request, $company_id, $template_id)
    {
        $template = $this->templateService->getTemplateById($template_id);
        return TemplateResource::make($template)->toResponse($request);
    }


    /**
     * Get company templates  of a specific datatable for the logged in user
     *
     * @param $company_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function all(TemplateAllRequest $request, $company_id, $template_id)
    {
        return $this->templateService->getTemplates($template_id);
    }


    /**
     * Download/show company template  f a specific datatable for the logged in user
     *
     * @param TemplateGetRequest $request
     * @param $company_id
     * @param $template_id
     * @param $entity
     * @param $type
     * @return BinaryFileResponse
     */
    public function get(TemplateGetRequest $request, $company_id, $template_id, $entity, $type)
    {
        return $this->templateService->getTemplate($template_id, $entity, $type);
    }

    /**
     * Update the table templates of a specific datatable for the logged in user
     *
     * @param TablePreferenceRequest $request
     * @param $company_id
     * @param $template_id
     * @param $entity
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(TemplateUpdateRequest $request, $company_id, $template_id, $entity)
    {
        return $this->templateService->updateTemplate($template_id, $request, $entity);
    }
}
