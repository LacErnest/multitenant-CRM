<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RecoverPasswordRequest;
use App\Models\User;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Forgot password
     *
     * Send a reset link to the given user.
     *
     * @OA\Post(
     *     path="/api/auth/password/recover",
     *     summary="Forgot password",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="User email",
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset link sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="We have emailed your password reset link!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to send reset link"
     *     )
     * )
     *
     * @param RecoverPasswordRequest $request
     * @return JsonResponse
     */
    public function sendResetLinkEmail(RecoverPasswordRequest $request)
    {
        $user = User::where([['email', $request->input('email')], ['primary_account', true]])->first();
        if ($user) {
            try {
                $response = $this->broker()->sendResetLink(
                    $request->only('email')
                );
                return $response == Password::RESET_LINK_SENT
                  ? $this->sendResetLinkResponse($response)
                  : $this->sendResetLinkFailedResponse($request, $response);
            } catch (\Exception $exception) {
                return response()->json([], 400);
            }
        }
        return response()->json(['message' => 'We have emailed your password reset link!'], 200);
    }

    /**
     * Get the response for a failed password reset.
     *
     * @param RecoverPasswordRequest $request
     * @param string $response
     * @return JsonResponse
     */
    protected function sendResetLinkFailedResponse(RecoverPasswordRequest $request, $response)
    {
        return response()->json(['message' => trans($response)], 400);
    }

    /**
     * Get the response for a successful password reset.
     *
     * @param string $response
     * @return JsonResponse
     */
    protected function sendResetLinkResponse($response)
    {
        return response()->json(['message' => trans($response)]);
    }
}
