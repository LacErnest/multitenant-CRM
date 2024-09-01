<?php

namespace App\Http\Controllers\Comment;

use App\DTO\Comments\CreateCommentDTO;
use App\Enums\TablePreferenceType;
use App\Enums\UserRole;
use Illuminate\Routing\Controller;
use App\Http\Requests\Comment\CommentCreateRequest;
use App\Http\Requests\Comment\CommentUpdateRequest;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Http\Resources\Comment\CommentResource;
use App\Http\Resources\Payment\InvoicePaymentResource;
use App\Models\Comment;
use App\Models\Invoice;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Database\Eloquent\Model;

class CommentController extends Controller
{
    protected CommentService $commentService;
    /**
     * @var Model
     */
    protected $model;

    public function __construct(Request $request, CommentService $commentService)
    {
        $modelName = $request->route('entity');
        $this->model = getModel($modelName);
        $this->commentService = $commentService;
    }

    /**
     * Get one comment of a specific entity
     * @param $entity_id
     * @param $entity_type
     * @param $comment_id
     * @return CommentResource
     */
    public function getSingleFromEntity(string $entity, string $id, string $comment_id):CommentResource
    {
        $this->commentService->checkAuthorization();

        $comment = $this->model::findOrFail($id)->comments()->findOrFail($comment_id);

        return CommentResource::make($comment);
    }

    /**
     * Create a comment for an entity
     * @param $entity_id
     * @param $entity_type
     * @param CommentCreateRequest $commentCreateRequest
     * @return JsonResponse
     */
    public function create($company_id, $project_id, string $entity, string $id, CommentCreateRequest $commentCreateRequest): JsonResponse
    {

        $commentDTO = new CreateCommentDTO($commentCreateRequest->allSafe());
        $entity = $this->model->findOrFail($id);
        $comment = $this->commentService->create($entity, $commentDTO);
        return response()->json([
          'message' => 'invoice comment created successfully.',
          'comment' => CommentResource::make($comment),
        ]);
    }

    /**
     * Update a comment
     * @param $entity_id
     * @param $entity_type
     * @param $comment_id
     * @param CommentUpdateRequest $request
     * @return mixed
     */
    public function update($company_id, $project_id, string $entity, string $id, string $comment_id, CommentUpdateRequest $request)
    {
        $comment = $this->model::findOrFail($id)->comments()->findOrFail($comment_id);

        if ($comment->created_by !== auth()->user()->id) {
            throw new UnauthorizedException();
        }

        $comment = $this->commentService->update($comment, $request);

        return response()->json([
          'message' => 'invoice payment updated successfully.',
          'comment' => CommentResource::make($comment)
        ]);
    }

    /**
     * Get all payments belonging to a invoices
     * @param string $entity
     * @return array
     */
    public function getAll(string $company_id, string $project_id, string $entity, string $entity_id): JsonResponse
    {
        $comments = $this->model::findOrFail($entity_id)->comments;

        $postResources = CommentResource::collection($comments);

        $data = $postResources->toArray(request());

        return response()->json($data);
    }

    /**
     * @param string $companyId
     * @param string $projectId
     * @param string $invoiceId
     * @param string $invoicePaymentIds
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($company_id, $project_id, string $entity, string $id, string $comment_id): JsonResponse
    {

        $this->commentService->checkAuthorization();

        $comment = $this->model::findOrFail($id)->comments()->findOrFail($comment_id);

        if ($comment->created_by !== auth()->user()->id) {
            throw new UnauthorizedException();
        }

        $status = $comment->delete();

        return response()->json([
          'message' => $status . ' comment(s) deleted successfully.'
        ]);
    }
}
