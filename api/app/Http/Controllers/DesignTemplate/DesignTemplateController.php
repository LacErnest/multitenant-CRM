<?php

namespace App\Http\Controllers\DesignTemplate;

use App\DTO\DesignTemplates\DesignTemplateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\DesignTemplate\DesignTemplateCreateRequest;
use App\Http\Requests\DesignTemplate\DesignTemplateUpdateRequest;
use App\Http\Resources\DesignTemplate\DesignTemplateResource;
use App\Services\DesignTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;

class DesignTemplateController extends Controller
{
    /**
     * @var DesignTemplateService
     */
    protected DesignTemplateService $designTemplateService;

    public function __construct(DesignTemplateService $designTemplateService)
    {
        $this->designTemplateService = $designTemplateService;
    }

    /**
     * Get all design templates documents
     *
     *  @param string $companyId
     *  @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $designTemplates = $this->designTemplateService->getAll();

        return DesignTemplateResource::collection($designTemplates)->toResponse($request);
    }

    /**
     * Get the document of a specifi design template
     *
     *  @param string $companyId
     *  @param Request $request
     * @return JsonResponse
     */
    public function view(string $companyId, $id, Request $request): JsonResponse
    {
        $designTemplate = $this->designTemplateService->findById($id);
        $resource = DesignTemplateResource::make($designTemplate)->toArray($request);
        $designTemplate = $this->designTemplateService->findById($id);
        $templateWordPath = $this->designTemplateService->getDesignTemplatePath($designTemplate);

        if (file_exists($templateWordPath)) {
            $resource['design'] = file_get_contents($templateWordPath);
        }

        return response()->json($resource);
    }


    /**
     * Create the document of a specific design template
     *
     * @param string $companyId
     * @param DesignTemplateCreateRequest $designTemplateUpdateRequest
     * @return JsonResponse
     */
    public function create(DesignTemplateCreateRequest $designTemplateUpdateRequest): JsonResponse
    {
        $designTemplateDTO = new DesignTemplateDTO($designTemplateUpdateRequest->allSafe());
        $designTemplate = $this->designTemplateService->create($designTemplateDTO);

        return DesignTemplateResource::make($designTemplate)->toResponse($designTemplateUpdateRequest);
    }

    /**
     * Update the document of a given design template
     *
     * @param string $companyId
     * @param DesignTemplateUpdateRequest $designTemplateUpdateRequest
     * @return JsonResponse
     */
    public function update(
        string $companyId,
        string $id,
        DesignTemplateUpdateRequest $designTemplateUpdateRequest
    ): JsonResponse {
        $designTemplateDTO = new DesignTemplateDTO($designTemplateUpdateRequest->allSafe());
        $designTemplate = $this->designTemplateService->update($id, $designTemplateDTO);

        return DesignTemplateResource::make($designTemplate)->toResponse($designTemplateUpdateRequest);
    }

    /**
     * Delte the document of the design template
     *
     * @param string $companyId
     * @param string $id
     * @return JsonResponse
     */
    public function delete(
        string $companyId,
        string $id
    ): JsonResponse {
        $status = $this->designTemplateService->delete($id);

        return response()->json(array('status' => $status));
    }

    /**
     * Delte the document of the design template
     *
     * @param string $companyId
     * @param Request $request
     * @return JsonResponse
     */

    public function uploadsImages(string $companyId, Request $request): JsonResponse
    {
        $request = Request::capture();
        $image = $request->file('image'); // Assuming image is sent as 'image'

        if (!$image) {
            return response()->json(['error' => 'No image uploaded'], 400);
        }

        $filename = uniqid('image_') . '.' . $image->getClientOriginalExtension();
        $imagePath = $companyId . '/uploads';

        $path = $image->storeAs($imagePath, $filename, 'design-template');
        return response()->json([
            'url' => env('APP_FRONTEND_URL').'/storage/'.$path,
        ]);
    }
}
