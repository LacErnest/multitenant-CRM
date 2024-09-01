<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Models\TrustedDevice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;
use WhichBrowser\Parser as BrowserDetect;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    protected $maxAttempts = 3; // Default is 5

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Login
     *
     * Handles a login request to the application.
     *
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", example="password"),
     *             @OA\Property(property="token", type="string", example="123456"),
     *             @OA\Property(property="trust_device", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid credentials or inactive account",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid credentials or inactive account")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity - Validation errors in input data",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid.")
     *         )
     *     )
     * )
     *
     * @param LoginRequest $request
     * @return JsonResponse|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function login(LoginRequest $request)
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        if (auth()->user()) {
            auth()->logout(true);
        }

        $token = auth()->attempt(array_merge($request->only(['email', 'password']), ['primary_account' => true]));

        if ($token) {
            if (auth()->user()->disabled_at) {
                return response()->json(['message' => trans('This account is inactive')], 401);
            }
            if (!config('auth.auth_2fa')) {
                return $this->sendLoginResponse($request, $token);
            }

            $userAgent = (new BrowserDetect(getallheaders()))->toString();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $trustedDevice = TrustedDevice::where([['user_id', auth()->user()->id], ['user_agent', $userAgent],
            ['ip_address', $ip]])->orderByDesc('created_at')->first();

            if ($trustedDevice && $trustedDevice->created_at > Carbon::now()->subDays(30)) {
                return $this->sendLoginResponse($request, $token);
            }

            if ($request->has('trust_device') && $request->input('trust_device') == true) {
                TrustedDevice::create([
                'user_id' => auth()->user()->id,
                'user_agent' => $userAgent,
                'ip_address' => $ip,
                ]);
            }

            if (auth()->user()->google2fa == 0) {
                return $this->sendLoginResponse($request, $token);
            } else {
                if ($request->has('token')) {
                    $secret = $request->input('token');
                    $user_secret = Crypt::decryptString(auth()->user()->google2fa_secret);
                    $google2fa = new Google2FA();
                    $valid = $google2fa->verifyKey($user_secret, $secret);
                    if ($valid) {
                        return $this->sendLoginResponse($request, $token);
                    } else {
                        $this->incrementLoginAttempts($request);
                        return response()->json(['message' => '2FA token is invalid'], 401);
                    }
                } else {
                    return response()->json(['message' => 'Please enter your google 2FA token.'], 200);
                }
            }
        }
        if ($token === null) {
            return response()->json(['message' => trans('This account is inactive')], 401);
        }
        $this->incrementLoginAttempts($request);
        return $this->sendFailedLoginResponse($request->input('email'));
    }

    /**
     * Logout
     *
     * Log the user out of the application.
     *
     * @OA\Post(
     *     path="/api/auth/logout",
     *     summary="Logout",
     *     tags={"Authentication"},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully logged out",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     *
     * @return JsonResponse
     */
    public function logout()
    {
        auth()->logout(true);

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Redirect the user after determining they are locked out.
     *
     * @param Request $request
     * @return JsonResponse
     */
    protected function sendLockoutResponse(Request $request)
    {
        $seconds = $this->limiter()->availableIn(
            $this->throttleKey($request)
        );

        return response()->json([
          'message' => trans('auth.throttle', ['seconds' => $seconds]),
        ], 429);
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param Request $request
     * @param $token
     * @return Response
     */
    protected function sendLoginResponse(Request $request, $token)
    {
        $this->clearLoginAttempts($request);
        $user = $this->guard()->user();
        if (!config('auth.auth_2fa')) {
            $user->google2fa = true;
        }
        return $this->authenticated($user, $token);
    }

    /**
     * Get the failed login response instance.
     * @param $email
     * @return JsonResponse
     */
    protected function sendFailedLoginResponse($email)
    {
        return response()->json(['message' => trans('auth.failed')], 401);
    }

    /**
     * The user has been authenticated.
     *
     * @param mixed $user
     * @param $token
     * @return mixed
     */
    protected function authenticated($user, $token)
    {
        JsonResource::wrap('user');

        return LoginResource::make($user)->additional([
          'access_token' => $token,
          'token_type' => 'bearer',
          'expires_in' => auth()->factory()->getTTL() * 60,
        ])->response();
    }
}
