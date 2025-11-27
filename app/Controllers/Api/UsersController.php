<?php

namespace App\Controllers\Api;

use App\Models\User;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * @OA\Tag(
 *     name="Users",
 *     description="User management endpoints"
 * )
 */
class UsersController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/Users",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get list with filtering",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="company_id",
     *         in="query",
     *         description="Filter by company ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of users",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User"))
     *         )
     *     )
     * )
     */
    public function index(): ResponseInterface
    {
        try {
            $page = (int)($this->request->getGet('page') ?? 1);
            $perPage = (int)($this->request->getGet('per_page') ?? 10);
            $companyId = $this->request->getGet('company_id');

            $query = $this->unitOfWork->Users()->getAll()
                ->include('Company')
                ->include('CustomField')
                ->include('UserDepartments')
                    ->thenInclude('Department')
                ->include('UserAuthorizations')
                    ->thenInclude('Authorization')
                ->include('UserOperationClaims')
                    ->thenInclude('OperationClaim');

            if ($companyId) {
                $query = $query->where(fn($u) => $u->CompanyId === (int)$companyId);
            }
            /** @var User $u */
            $query = $query->where(fn($u) => $u->Company->Name =="Firma 1" && $u->CustomField->CustomField01=="xxx");

            $total = $query->count();
            $users = $query->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->toList();

            return $this->paginatedResponse(
                $this->entityToArray($users),
                $page,
                $perPage,
                $total
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/Users/{id}",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get user by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function show($id): ResponseInterface
    {
        try {
            $user = $this->unitOfWork->Users()->getById((int)$id);

            if (!$user) {
                return $this->notFound('User not found');
            }

            return $this->success($this->entityToArray($user));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/Users/{id}/full",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get user with all relations",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User with all relations",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function showFull($id): ResponseInterface
    {
        try {
            $user = $this->unitOfWork->Users()->getWithAllRelations((int)$id);

            if (!$user) {
                return $this->notFound('User not found');
            }

            return $this->success($this->entityToArray($user));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/Users",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     summary="Create new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserCreate")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function create(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);

            if (!$data) {
                return $this->validationError(['body' => 'Request body is required']);
            }

            // Validation
            $errors = $this->validateUser($data);
            if (!empty($errors)) {
                return $this->validationError($errors);
            }

            $user = $this->createEntityFromRequest(User::class, $data);
            $user->CreatedAt = new \DateTime();

            $this->unitOfWork->Users()->add($user);
            $this->unitOfWork->saveChanges();

            return $this->success($this->entityToArray($user), 'User created successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/Users/{id}",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     summary="Update user",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserUpdate")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully"
     *     ),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update($id): ResponseInterface
    {
        try {
            $user = $this->unitOfWork->Users()->getById((int)$id);

            if (!$user) {
                return $this->notFound('User not found');
            }

            $data = $this->request->getJSON(true);

            if (!$data) {
                return $this->validationError(['body' => 'Request body is required']);
            }

            // Update properties
            foreach ($data as $key => $value) {
                if (property_exists($user, $key) && $key !== 'Id') {
                    $user->$key = $value;
                }
            }

            $user->UpdatedAt = new \DateTime();

            $this->unitOfWork->Users()->update($user);
            $this->unitOfWork->saveChanges();

            return $this->success($this->entityToArray($user), 'User updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/Users/{id}",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     summary="Delete user",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully"
     *     ),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    public function delete($id): ResponseInterface
    {
        try {
            $user = $this->unitOfWork->Users()->getById((int)$id);

            if (!$user) {
                return $this->notFound('User not found');
            }

            $this->unitOfWork->Users()->remove($user);
            $this->unitOfWork->saveChanges();

            return $this->success(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/Users/{id}/soft-delete",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     summary="Soft delete user",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User soft deleted successfully"
     *     )
     * )
     */
    public function softDelete($id): ResponseInterface
    {
        try {
            $user = $this->unitOfWork->Users()->getById((int)$id);

            if (!$user) {
                return $this->notFound('User not found');
            }

            $this->unitOfWork->Users()->softDelete($user);
            $this->unitOfWork->saveChanges();

            return $this->success(null, 'User soft deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/Users/{id}/restore",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     summary="Restore soft deleted user",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User restored successfully"
     *     )
     * )
     */
    public function restore($id): ResponseInterface
    {
        try {
            $restored = $this->unitOfWork->Users()->restore((int)$id);

            if (!$restored) {
                return $this->error('User not found or not deleted', 404);
            }

            $this->unitOfWork->saveChanges();

            return $this->success(null, 'User restored successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/Users/{id}/departments",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     summary="Add department to user",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="department_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Department added successfully")
     * )
     */
    public function addDepartment($id): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            $departmentId = $data['department_id'] ?? null;

            if (!$departmentId) {
                return $this->validationError(['department_id' => 'Department ID is required']);
            }

            $this->unitOfWork->Users()->addDepartment((int)$id, (int)$departmentId);
            $this->unitOfWork->saveChanges();

            return $this->success(null, 'Department added successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/Users/{id}/authorizations",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     summary="Add authorization to user",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="authorization_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Authorization added successfully")
     * )
     */
    public function addAuthorization($id): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            $authorizationId = $data['authorization_id'] ?? null;

            if (!$authorizationId) {
                return $this->validationError(['authorization_id' => 'Authorization ID is required']);
            }

            $this->unitOfWork->Users()->addAuthorization((int)$id, (int)$authorizationId);
            $this->unitOfWork->saveChanges();

            return $this->success(null, 'Authorization added successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/Users/{id}/operation-claims",
     *     tags={"Users"},
     *     security={{"bearerAuth":{}}},
     *     summary="Add operation claim to user",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation_claim_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Operation claim added successfully")
     * )
     */
    public function addOperationClaim($id): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            $operationClaimId = $data['operation_claim_id'] ?? null;

            if (!$operationClaimId) {
                return $this->validationError(['operation_claim_id' => 'Operation claim ID is required']);
            }

            $this->unitOfWork->Users()->addOperationClaim((int)$id, (int)$operationClaimId);
            $this->unitOfWork->saveChanges();

            return $this->success(null, 'Operation claim added successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * Validate user data
     */
    private function validateUser(array $data): array
    {
        $errors = [];

        if (empty($data['FirstName'])) {
            $errors['FirstName'] = 'First name is required';
        }

        if (empty($data['LastName'])) {
            $errors['LastName'] = 'Last name is required';
        }

        if (empty($data['CompanyId'])) {
            $errors['CompanyId'] = 'Company ID is required';
        }

        return $errors;
    }
}

