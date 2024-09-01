<?php

namespace App\Exceptions\Formatters;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

class CustomModelNotFoundExceptionFormatter
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param Request $request
     *
     * @param $e
     * @return JsonResponse
     */
    public function render(Request $request, Throwable $e)
    {
        $response = new JsonResponse();
        $response->setStatusCode(404);
        $data = $response->getData(true);

        if (config('app.debug')) {
            $data = array_merge($data, [
            'code' => $e->getCode(),
            'message' => 'no results for model ' . basename($e->getModel()),
            'exception' => (string)$e,
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            ]);
        } else {
            $data['message'] = 'You don\'t have the permission to access this resource.';
        }

        $response->setData($data);

        try {
            $token = JWTAuth::parseToken()->refresh();
            $response->headers->set('X-Authorization', 'Bearer ' . $token);
        } catch (Exception $e) {
            if ($response->headers->has('X-Authorization')) {
                $response->headers->remove('X-Authorization');
            }
        }

        return $response;
    }
}
