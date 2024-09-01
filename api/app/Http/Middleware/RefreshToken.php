<?php

namespace App\Http\Middleware;

use App\Models\ResourceLogin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\JWTAuth;

class RefreshToken extends BaseMiddleware
{
    protected $auth;

    public function __construct(JWTAuth $auth)
    {
        parent::__construct($auth);
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $resourceToken = $request->header('X-Auth');
        $hasUserToken = $this->auth->parser()->setRequest($request)->hasToken();
        $hasResourceToken = !($resourceToken === null);

        if (!$hasUserToken && !$hasResourceToken) {
            return $response;
        } elseif ($hasUserToken && $hasResourceToken) {
            throw new UnauthorizedHttpException('jwt-auth', 'Authorization and X-Auth headers are both set');
        } elseif ($hasUserToken) {
            try {
                $token = $this->auth->parseToken();
                $token->checkOrFail();
            } catch (JWTException $e) {
                throw new UnauthorizedHttpException('jwt-auth', $e->getMessage(), $e, $e->getCode());
            }

              return $this->setAuthenticationHeader($response, $token->refresh());
        } else {
            return $response;
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function setAuthenticationHeader($response, $token = null)
    {
        $token = $token ?? $this->auth->refresh();
        $response->headers->set('X-Authorization', 'Bearer ' . $token);

        return $response;
    }
}
