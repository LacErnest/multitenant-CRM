<?php


namespace App\Exceptions\Formatters;


use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

class CustomTokenInvalidExceptionFormatter
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
        $response->setStatusCode(401);
        $data = $response->getData(true);

        if (config('app.debug')) {
            $data = array_merge($data, [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'exception' => (string)$e,
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            ]);
        } else {
            $data['message'] = 'You are not authorised';
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
