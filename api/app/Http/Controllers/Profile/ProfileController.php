<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ActivateTwoFactorRequest;
use App\Http\Resources\Auth\TwoFactorResource;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;

class ProfileController extends Controller
{

    /**
     * Get a Google 2FA secret key
     *
     * Retrieves and saves a Google Authenticator secret key for the authenticated user.
     *
     * @OA\Get(
     *     path="/api/auth/profile/2fa",
     *     summary="Get Google 2FA secret key",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Google 2FA secret key generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="key", type="string", example="generated_secret_key")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: Invalid or missing JWT token"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error"
     *     )
     * )
     *
     * @return JsonResponse
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function getTwoFactor()
    {
        $userProfiles = User::where('email', auth()->user()->email)->get();
        $google2fa = new Google2FA();
        $secretKey = $google2fa->generateSecretKey(32);

        foreach ($userProfiles as $userProfile) {
            $userProfile->google2fa_secret = Crypt::encryptString($secretKey);
            $userProfile->google2fa = 0;
            $userProfile->save();
        }

        return response()->json(['key' => $secretKey]);
    }

    /**
     * Verify and activate Google 2FA
     *
     * Verifies the Google Authenticator token and activates two-factor authentication for the authenticated user.
     *
     * @OA\Put(
     *     path="/api/auth/profile/2fa",
     *     summary="Activate Google 2FA",
     *     tags={"Profile"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Google Authenticator token",
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", example="123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="2-factor authentication activated successfully",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: Invalid or missing JWT token"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity: Invalid token or failed activation"
     *     )
     * )
     *
     * @param ActivateTwoFactorRequest $request
     * @return JsonResponse
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     */
    public function activateTwoFactor(ActivateTwoFactorRequest $request)
    {
        $user = auth()->user();
        $secret = $request->input('token');
        if ($user->google2fa_secret != null && $user->google2fa === 0) {
            try {
                $user_secret = Crypt::decryptString($user->google2fa_secret);
                $google2fa = new Google2FA();
                $valid = $google2fa->verifyKey($user_secret, $secret);
                if ($valid) {
                    $userProfiles = User::where('email', $user->email)->get();

                    foreach ($userProfiles as $userProfile) {
                        $userProfile->google2fa = 1;
                        $userProfile->save();
                    }

                    return TwoFactorResource::make($user)->additional([
                    'message' => '2-factor authentication activated'
                    ])->response();
                }
            } catch (DecryptException $e) {
            }
            $user->google2fa_secret = null;
            $user->save();
            return response()->json(['message' => '2-factor authentication failed. Please get a new key.'], 422);
        }
        return response()->json(['message' => 'You need to get a key first to enable 2-factor authentication.'], 422);
    }
}
