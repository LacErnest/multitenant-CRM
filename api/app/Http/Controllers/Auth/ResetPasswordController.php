<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Tymon\JWTAuth\JWTGuard;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    private $token;

    /**
     * Reset password
     *
     * Reset the given user's password
     *
     * @OA\Post(
     *     path="/api/auth/password/reset",
     *     summary="Reset password",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User credentials and password reset token",
     *         @OA\JsonContent(
     *             required={"token", "email", "password", "password_confirmation"},
     *             @OA\Property(property="token", type="string", example="token_value"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="new_password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="new_password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password reset successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to reset password"
     *     )
     * )
     *
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function reset(ResetPasswordRequest $request)
    {
        $request->merge(['token' => $request->input('token')]);
        $response = $this->broker()->reset(
            $this->credentials($request),
            function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        return $response == Password::PASSWORD_RESET
          ? $this->sendResetResponse($response)
          : $this->sendResetFailedResponse($request, $response);
    }

    /**
     * Sets the new password.
     *
     * @param $user
     * @param string $password
     * @return void
     */
    protected function resetPassword($user, $password)
    {
        $userProfiles = User::where('email', $user->email)->get();

        foreach ($userProfiles as $userProfile) {
            $userProfile->password = $password;
            $userProfile->save();
        }

        event(new PasswordReset($user));

      /** Logging user in */
        $this->token = $this->guard()->login($user);
    }

    /**
     * Get the guard to be used during password reset.
     *
     * @return Guard|StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }

    /**
     * Get the response for a failed password reset.
     *
     * @param ResetPasswordRequest $request
     * @param string $response
     * @return JsonResponse
     */
    protected function sendResetFailedResponse(ResetPasswordRequest $request, $response)
    {
        return response()->json(['message' => trans($response)], 400);
    }

    /**
     * Get the response for a successful password reset.
     *
     * @param string $response
     * @return JsonResponse
     */
    protected function sendResetResponse($response)
    {
        JsonResource::wrap('user');

        return LoginResource::make($this->guard()->user())->additional([
          'message' => trans($response),
          'access_token' => $this->token,
          'token_type' => 'bearer',
          'expires_in' => auth()->factory()->getTTL() * 60,
        ])->response();
    }
}
