<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\ResourceRepositoryInterface;
use App\Models\Resource;

class EloquentResourceRepository extends EloquentRepository implements ResourceRepositoryInterface
{
    public const CURRENT_MODEL = Resource::class;

    protected Resource $resourceModel;

    public function __construct(Resource $resourceModel)
    {
        $this->resourceModel = $resourceModel;
    }

    public function getResourcesWithXero()
    {
        return $this->resourceModel->whereNotNull('xero_id')->get();
    }
}
