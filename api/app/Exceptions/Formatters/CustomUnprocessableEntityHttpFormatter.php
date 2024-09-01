<?php

namespace App\Exceptions\Formatters;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

class CustomUnprocessableEntityHttpFormatter
{
    /**
     * Render the exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param Throwable $e
     * @return JsonResponse
     */
    public function render($request, Throwable $e)
    {
        $response = new JsonResponse();
        $response->setStatusCode(422);
        $data = $response->getData(true);

        $data = array_merge($data, [
          'status' => '422',
          'code' => $e->getCode(),
          'title' => 'Validation error',
          'message' => $e->getMessage(),
        ]);
        if ($e instanceof ValidationException) {
            $data['message'] = $e->validator->getMessageBag();
        }
        $response->setData($data);

        try {
            $token = JWTAuth::parseToken()->refresh();
            $response->headers->set('X-Authorization', 'Bearer ' . $token);
        } catch (Exception $e) {
            logger($e);
            if ($response->headers->has('X-Authorization')) {
                $response->headers->remove('X-Authorization');
            }
        }

        return $response;
    }
}
