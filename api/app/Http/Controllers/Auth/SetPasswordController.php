<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SetPasswordRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class SetPasswordController extends Controller
{
    use ResetsPasswords;

    private $token;

    /**
     * Set password
     *
     * Set password for a company user.
     *
     * @OA\Post(
     *     path="/api/auth/password/set",
     *     summary="Set password",
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
     *         description="Password set successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password set successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to set password"
     *     )
     * )
     *
     * @param SetPasswordRequest $request
     * @return JsonResponse
     */
    public function set(SetPasswordRequest $request)
    {
        $request->merge(['token' => $request->input('token')]);
        $response = $this->broker('new_users')->reset(
            $this->credentials($request),
            function ($user, $password) {
                $this->setPassword($user, $password);
            }
        );

        return $response == Password::PASSWORD_RESET
          ? $this->sendSetResponse($response)
          : $this->sendResetFailedResponse($request, $response);
    }

    /**
     * Sets the given user's password.
     *
     * @param $user
     * @param string $password
     * @return void
     */
    protected function setPassword($user, $password)
    {
        $userProfiles = User::where('email', $user->email)->get();

        foreach ($userProfiles as $userProfile) {
            $userProfile->password = $password;
            $userProfile->save();
        }
    }

    /**
     * Get the guard to be used during set password.
     *
     * @return Guard|StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard();
    }

    /**
     * Get the response for a failed set password.
     *
     * @param SetPasswordRequest $request
     * @param string $response
     * @return JsonResponse
     */
    protected function sendResetFailedResponse(SetPasswordRequest $request, $response)
    {
        return response()->json(['message' => trans($response)], 400);
    }

    /**
     * Get the response for a successful set password.
     *
     * @param string $response
     * @return JsonResponse
     */
    protected function sendSetResponse($response)
    {
        return response()->json(['message' => 'Password set successfully.']);
    }

    public function broker($name = 'users')
    {
        return Password::broker($name);
    }
}
