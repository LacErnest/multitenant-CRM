<?php

namespace App\Http\Controllers\User;

use App\Enums\TablePreferenceType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateMailPreferencesRequest;
use App\Http\Requests\User\UserCreateRequest;
use App\Http\Requests\User\UserDeleteRequest;
use App\Http\Requests\User\UserSuggestForAllCompaniesRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Http\Resources\User\MailPreferenceResource;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;

class UserController extends Controller
{
    protected UserService $user_service;

    protected string $model = User::class;

    public function __construct(UserService $user_service)
    {
        $this->user_service = $user_service;
    }

    /**
     * Get all users of a specific company
     * @OA\Get(
     *      path="/{companyId}/users/",
     *      operationId="getAllUsers",
     *      tags={"Users"},
     *      summary="Get all users",
     *      description="Returns all users of a specific company.",
     *      @OA\Parameter(
     *          name="companyId",
     *          in="path",
     *          description="Company ID",
     *          required=true,
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="entity",
     *          in="path",
     *          description="Entity ID",
     *          required=true,
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="page",
     *          in="query",
     *          description="Page number",
     *          required=false,
     *          @OA\Schema(
     *              type="integer",
     *              default=0
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="amount",
     *          in="query",
     *          description="Number of users per page",
     *          required=false,
     *          @OA\Schema(
     *              type="integer",
     *              default=10
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized"
     *      ),
     *      security={
     *         {"api_key": {}}
     *     }
     * )
     *
     * @param  string  $companyId
     * @param  Request  $request
     * @param  int|null  $entity
     * @return array
     * @throws UnauthorizedException
     */
    public function getAll(string $companyId, Request $request, $entity = null): array
    {
        if (UserRole::isAdmin(auth()->user()->role) || UserRole::isOwner(auth()->user()->role) ||
          UserRole::isOwner_read(auth()->user()->role)) {
            return parent::getAll($companyId, $request, TablePreferenceType::users()->getIndex());
        }

        throw new UnauthorizedException();
    }

    /** @OA\Get(
    *      path="/api/users/{company_id}/{user_id}",
    *      operationId="getSingleUser",
    *      tags={"Users"},
    *      summary="Get a single user by company and user ID",
    *      description="Returns a single user's information by company and user ID.",
    *      @OA\Parameter(
    *          name="company_id",
    *          in="path",
    *          required=true,
    *          description="ID of the company",
    *          @OA\Schema(
    *              type="integer",
    *              format="int64"
    *          )
    *      ),
    *      @OA\Parameter(
    *          name="user_id",
    *          in="path",
    *          required=true,
    *          description="ID of the user",
    *          @OA\Schema(
    *              type="integer",
    *              format="int64"
    *          )
    *      ),
    *      @OA\Response(
    *          response=200,
    *          description="Successful operation",
    *        )
    *      ),
    *      @OA\Response(
    *          response=404,
    *          description="Not Found",
    *          @OA\JsonContent(
    *              @OA\Property(
    *                  property="message",
    *                  type="string",
    *                  example="User not found."
    *              )
    *          )
    *      )
    * )
    *
     * Get one user of a specific company
     *
     * Retrieves details of a specific user within a company based on user role permissions.
     *
     * @OA\Get(
     *     path="/api/{company_id}/users/{user_id}",
     *     summary="Get one user of a specific company",
     *     tags={"Users"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User does not have necessary permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found: User with given ID not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     *
     * @param string $company_id
     * @param string $user_id
     * @return \Illuminate\Http\JsonResponse
     * @throws UnauthorizedException
     */
    public function getSingle($company_id, $user_id)
    {
        if (UserRole::isOwner(auth()->user()->role) || UserRole::isOwner_read(auth()->user()->role)) {
            $result = parent::getSingle($company_id, $user_id);
            if (UserRole::isAdmin($result->resource->role)) {
                throw new UnauthorizedException();
            }
            return $result;
        }

        if (UserRole::isAdmin(auth()->user()->role)) {
            return parent::getSingle($company_id, $user_id);
        }
        throw new UnauthorizedException();
    }

    /**
     * Suggest users or related data based on the provided value.
     *
     * @OA\Post(
     *      path="/api/suggest/{company_id}",
     *      operationId="suggestUsers",
     *      tags={"Users"},
     *      summary="Suggest users or related data",
     *      description="Suggests users or related data based on the provided value.",
     *      @OA\Parameter(
     *          name="company_id",
     *          in="path",
     *          required=true,
     *          description="ID of the company",
     *          @OA\Schema(
     *              type="integer",
     *              format="int64"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Data for suggestion",
     *          @OA\JsonContent(
     *              type="object",
     *              example={"key": "value"}
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="value",
     *          in="query",
     *          required=false,
     *          description="Value for suggestion",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthorized",
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="message",
     *                  type="string",
     *                  example="Unauthorized."
     *              )
     *          )
     *      )
     * )
     * Get certain users of a specific company using autocomplete
     *
     * Retrieves users of a specific company based on the provided search value for autocomplete.
     *
     * @OA\Get(
     *     path="/api/{company_id}/users/suggest/{value}",
     *     summary="Get certain users of a specific company using autocomplete",
     *     tags={"Users"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="value",
     *         in="path",
     *         required=false,
     *         description="Search value for autocomplete",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User does not have necessary permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     *
     * @param string $company_id
     * @param Request $request
     * @param string|null $value
     * @return \Illuminate\Http\JsonResponse
     * @throws UnauthorizedException
     */
    public function suggest($company_id, Request $request, $value = null)
    {
        if (UserRole::isAdmin(auth()->user()->role) || UserRole::isOwner(auth()->user()->role) ||
          UserRole::isSales(auth()->user()->role) || UserRole::isAccountant(auth()->user()->role) ||
          UserRole::isOwner_read(auth()->user()->role)) {
            return $this->user_service->suggest($company_id, $request, $value);
        }
        throw new UnauthorizedException();
    }

    /**
     * Get certain users with salesperson role for all companies using autocomplete
     *
     * Retrieves users with the salesperson role across all companies based on the provided value for autocomplete.
     *
     * @OA\Get(
     *     path="/api/suggest/sales_persons/{value}",
     *     summary="Get salespersons for all companies (autocomplete)",
     *     tags={"User"},
     *     @OA\Parameter(
     *         name="value",
     *         in="path",
     *         required=true,
     *         description="Search value for autocomplete",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="company",
     *         in="query",
     *         description="Company ID to filter salespersons",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: Invalid or missing JWT token"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found: Company not found or user not authorized"
     *     )
     * )
     *
     * @param UserSuggestForAllCompaniesRequest $request
     * @param string $value
     * @return \Illuminate\Http\JsonResponse
     */
    public function suggestSalesPersonThroughAllCompanies(UserSuggestForAllCompaniesRequest $request, $value)
    {
        $companyId = null;
        if (!empty($request->query->get('company'))) {
            $companyId = $request->query->get('company');
        } elseif (UserRole::isOwner(auth()->user()->role) || UserRole::isOwner_read(auth()->user()->role)) {
            $companyId = auth()->user()->company_id;
        }

        return $this->user_service->suggestSalesPersonThroughAllCompanies($value, $companyId);
    }

    /**
     * Create a new user of a specific company
     *
     * Creates a new user within a specific company based on user role permissions.
     *
     * @OA\Post(
     *     path="/api/{company_id}/users",
     *     summary="Create a new user of a specific company",
     *     tags={"Users"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User does not have necessary permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     *
     * @param string $company_id
     * @param UserCreateRequest $request
     * @return UserResource
     * @throws UnauthorizedException
     */
    public function createUser($company_id, UserCreateRequest $request)
    {
        if (UserRole::isAdmin(auth()->user()->role) || UserRole::isOwner(auth()->user()->role)) {
            $user = $this->user_service->create($company_id, $request);

            return UserResource::make($user);
        }
        throw new UnauthorizedException();
    }

    /**
     * Update a user of a specific company
     *
     * Updates a user's information based on the company ID and user ID.
     *
     * @OA\Put(
     *     path="/api/{company_id}/users/{user_id}",
     *     summary="Update a user of a specific company",
     *     tags={"Users"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="User data to update",
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User is not an admin or owner",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found: User or company not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     *
     * @param string $company_id
     * @param string $user_id
     * @param UserUpdateRequest $request
     * @return UserResource
     * @throws UnauthorizedException
     * @throws ModelNotFoundException
     */
    public function updateUser($company_id, $user_id, UserUpdateRequest $request)
    {
        if (UserRole::isAdmin(auth()->user()->role) || UserRole::isOwner(auth()->user()->role)) {
            $user = $this->user_service->update($company_id, $user_id, $request);
            return UserResource::make($user);
        }
        throw new UnauthorizedException();
    }

    /**
     * Delete a user of a specific company
     *
     * Deletes user(s) based on the company ID and request parameters.
     *
     * @OA\Delete(
     *     path="/api/{company_id}/users",
     *     summary="Delete a user of a specific company",
     *     tags={"Users"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="User IDs to delete",
     *         @OA\JsonContent(
     *             required={"user_ids"},
     *             @OA\Property(property="user_ids", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="1 user(s) deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User is not an admin or owner",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     *
     * @param string $company_id
     * @param UserDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws UnauthorizedException
     */
    public function deleteUser($company_id, UserDeleteRequest $request)
    {
        if (UserRole::isAdmin(auth()->user()->role) || UserRole::isOwner(auth()->user()->role)) {
            $status = $this->user_service->delete($request->all());
            return response()->json(['message' => $status . ' user(s) deleted successfully.']);
        }
        throw new UnauthorizedException();
    }

    /**
     * Get mail preferences of a company owner
     *
     * Retrieves mail preferences of a company owner based on the company ID.
     *
     * @OA\Get(
     *     path="/api/{company_id}/users/mail_preferences",
     *     summary="Get mail preferences of a company owner",
     *     tags={"Users"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User is not the owner of the company",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found: Owner not found or company does not exist",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Owner not found")
     *         )
     *     )
     * )
     *
     * @param string $company_id
     * @return MailPreferenceResource
     * @throws ModelNotFoundException
     */
    public function getMailPreferences($company_id)
    {
        if (UserRole::isOwner(auth()->user()->role)) {
            $settings = $this->user_service->getMailPreferences();
            return MailPreferenceResource::make($settings);
        }
        throw new ModelNotFoundException();
    }

    /**
     * Update mail preferences of a company owner
     *
     * Updates the mail preferences of a company owner based on the company ID.
     *
     * @OA\Put(
     *     path="/api/{company_id}/users/mail_preferences",
     *     summary="Update mail preferences of a company owner",
     *     tags={"Users"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Mail preferences data",
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User is not the owner of the company",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found: Owner not found or company does not exist",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Owner not found")
     *         )
     *     )
     * )
     *
     * @param string $company_id
     * @param UpdateMailPreferencesRequest $request
     * @return MailPreferenceResource
     * @throws ModelNotFoundException
     */
    public function UpdateMailPreferences($company_id, UpdateMailPreferencesRequest $request)
    {
        if (UserRole::isOwner(auth()->user()->role)) {
            $settings = $this->user_service->updateMailPreferences($request);
            return MailPreferenceResource::make($settings);
        }
        throw new ModelNotFoundException();
    }

    /**
     * Get certain users of a specific company using autocomplete for assigning project manager
     *
     * Retrieves users of a specific company based on the provided search value for autocomplete, specifically for assigning project managers.
     *
     * @OA\Get(
     *     path="/api/{company_id}/users/pm_suggest/{value}",
     *     summary="Get certain users of a specific company using autocomplete for assigning project manager",
     *     tags={"Users"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="value",
     *         in="path",
     *         required=false,
     *         description="Search value for autocomplete",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User does not have necessary permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     *
     * @param string $company_id
     * @param Request $request
     * @param string|null $value
     * @return JsonResponse
     * @throws UnauthorizedException
     */
    public function suggestProjectManager(string $companyId, Request $request, $value = null): JsonResponse
    {
        if (UserRole::isAdmin(auth()->user()->role) || UserRole::isOwner(auth()->user()->role) ||
          UserRole::isSales(auth()->user()->role) || UserRole::isAccountant(auth()->user()->role) ||
          UserRole::isOwner_read(auth()->user()->role)) {
            return $this->user_service->suggestProjectManager($companyId, $value);
        }
        throw new UnauthorizedException();
    }

    /**
     * Resend verification link to a user
     *
     * Resends the verification link to a user within a specific company based on user role permissions.
     *
     * @OA\Post(
     *     path="/api/{company_id}/users/{user_id}/resend_link",
     *     summary="Resend verification link to a user",
     *     tags={"Users"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Verification link resent successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User does not have necessary permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found: User with given ID not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     *
     * @param string $company_id
     * @param string $user_id
     * @return JsonResponse
     * @throws UnauthorizedException
     */
    public function resendLink(string $companyId, string $userId): JsonResponse
    {
        if (UserRole::isAdmin(auth()->user()->role) || UserRole::isOwner(auth()->user()->role)) {
            $message = $this->user_service->resendLink($userId);

            return response()->json($message);
        }

        throw new UnauthorizedException();
    }

    /**
     * Toggle user status
     *
     * Toggles the status (active/inactive) of a user within a specific company.
     *
     * @OA\Put(
     *     path="/api/{company_id}/users/{user_id}/status",
     *     summary="Toggle user status",
     *     tags={"Users"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="company_id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         required=true,
     *         description="User ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized: User does not have necessary permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found: User with given ID not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     *
     * @param string $companyId
     * @param string $userId
     * @return UserResource
     * @throws UnauthorizedException
     */
    public function toggleStatus(string $companyId, string $userId): UserResource
    {
        if (UserRole::isAdmin(auth()->user()->role) || UserRole::isOwner(auth()->user()->role)) {
            $user = User::findOrFail($userId);
            $user->disabled_at = $user->disabled_at ? null : now();
            $user->save();
            return UserResource::make($user);
        }

        throw new UnauthorizedException();
    }
}
