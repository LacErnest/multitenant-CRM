<?php


namespace App\Services;

use App\Contracts\Repositories\CommentRepositoryInterface;
use App\DTO\Comments\CreateCommentDTO;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\Comment;
use App\Repositories\CommentRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Support\Str;

/**
 * CommentService
 */
class CommentService
{
    protected CommentRepositoryInterface $commentRepository;

    public function __construct(
        CommentRepositoryInterface $commentRepository
    ) {
        $this->commentRepository = $commentRepository;
    }

    /**
     * Create new comment for a specific entity
     * @param Model $entity
     * @param CreateCommentDTO $CreateCommentDTO
     * @return Comment
     */
    public function create(Model $entity, CreateCommentDTO $createCommentDTO): Comment
    {
        $comment = $entity->comments()->create(
            array_merge($createCommentDTO->toArray(), [
              'created_by' => auth()->user()->id ?? $entity->created_by
            ])
        );
        $entity->refresh();

        return $comment;
    }

    /**
     * Update existing comment
     * @param Model $entity
     * @param BaseRequest $request
     * @return Comment
     */
    public function update(Comment $comment, BaseRequest $request): Comment
    {
        $this->commentRepository->update(
            $comment->id,
            array_merge($request->allSafe())
        );
        $comment->refresh();
        return $comment;
    }


    /**
     * Check connected user authorization
     */
    public function checkAuthorization(): void
    {
        if (
          UserRole::isSales(auth()->user()->role) || UserRole::isPm(auth()->user()->role) ||
          UserRole::isHr(auth()->user()->role) || UserRole::isOwner_read(auth()->user()->role) ||
          UserRole::isPm_restricted(auth()->user()->role)
        ) {
            throw new UnauthorizedException();
        }
    }

    /**
     * Get the link entity model by type and id
     * @param string $entity_type
     * @param string $entity_id
     * @return Model
     */
    public function getLinkedEntityModel(string $entity_type, string $entity_id): Model
    {
        $container = app();
        $entityClassName = Str::studly($entity_type);

        if ($container->bound($entityClassName)) {
            $entity = $container->make($entityClassName);
            return $entity->findOrFail($entity_id);
        }

        throw new \InvalidArgumentException("Unknown entity type: $entity_type");
    }

    /**
     * Get the link entity resource by type and id
     * @param string $entity_type
     * @return string
     */
    public function getLinkedEntityResource(string $entity_type): string
    {
        $entityClassName = Str::studly($entity_type);
        $resourceClassName = "App\Http\Resources\\{$entityClassName}Resource";

        if (class_exists($resourceClassName)) {
            return $resourceClassName;
        }

        throw new \InvalidArgumentException("Unknown entity type: $entity_type");
    }
}
