<?php


namespace App\Services;


use App\Http\Requests\Service\ServiceCreateRequest;
use App\Http\Requests\Service\ServiceUpdateRequest;
use App\Repositories\ServiceRepository;
use Illuminate\Http\JsonResponse;

class ServiceService
{
    protected ServiceRepository $service_repository;

    public function __construct(ServiceRepository $service_repository)
    {
        $this->service_repository = $service_repository;
    }

    public function create(ServiceCreateRequest $request)
    {
        return $this->service_repository->create($request->allSafe());
    }

    public function update($service_id, ServiceUpdateRequest $request)
    {
        return $this->service_repository->update($service_id, $request->allSafe());
    }

    public function delete($array)
    {
        return $this->service_repository->delete($array);
    }

    public function suggest($value, $attributes): JsonResponse
    {
        return $this->service_repository->suggest($value, $attributes);
    }
}
