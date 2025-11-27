<?php

namespace App\Controllers\Api;

use App\Models\Authorization;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * @OA\Tag(
 *     name="Authorizations",
 *     description="Authorization management endpoints"
 * )
 */
class AuthorizationsController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/Authorizations",
     *     tags={"Authorizations"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get list with filtering",
     *     @OA\Response(response=200, description="List of authorizations")
     * )
     */
    public function index(): ResponseInterface
    {
        try {
            $authorizations = $this->unitOfWork->Authorizations()->getAllOrderedByName();
            return $this->success($this->entityToArray($authorizations));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/Authorizations/{id}",
     *     tags={"Authorizations"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get authorization by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Authorization details"),
     *     @OA\Response(response=404, description="Authorization not found")
     * )
     */
    public function show($id): ResponseInterface
    {
        try {
            $authorization = $this->unitOfWork->Authorizations()->getById((int)$id);
            if (!$authorization) {
                return $this->notFound('Authorization not found');
            }
            return $this->success($this->entityToArray($authorization));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/Authorizations",
     *     tags={"Authorizations"},
     *     security={{"bearerAuth":{}}},
     *     summary="Create new authorization",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Name", type="string"),
     *             @OA\Property(property="Description", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Authorization created successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function create(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            if (empty($data['Name'])) {
                return $this->validationError(['Name' => 'Name is required']);
            }

            $authorization = $this->createEntityFromRequest(Authorization::class, $data);
            $authorization->CreatedAt = new \DateTime();

            $this->unitOfWork->Authorizations()->add($authorization);
            $this->unitOfWork->saveChanges();

            return $this->success($this->entityToArray($authorization), 'Authorization created successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/Authorizations/{id}",
     *     tags={"Authorizations"},
     *     security={{"bearerAuth":{}}},
     *     summary="Update authorization",
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
     *             @OA\Property(property="Name", type="string"),
     *             @OA\Property(property="Description", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Authorization updated successfully"),
     *     @OA\Response(response=404, description="Authorization not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update($id): ResponseInterface
    {
        try {
            $authorization = $this->unitOfWork->Authorizations()->getById((int)$id);
            if (!$authorization) {
                return $this->notFound('Authorization not found');
            }

            $data = $this->request->getJSON(true);
            foreach ($data as $key => $value) {
                if (property_exists($authorization, $key) && $key !== 'Id') {
                    $authorization->$key = $value;
                }
            }
            $authorization->UpdatedAt = new \DateTime();

            $this->unitOfWork->Authorizations()->update($authorization);
            $this->unitOfWork->saveChanges();

            return $this->success($this->entityToArray($authorization), 'Authorization updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/Authorizations/{id}",
     *     tags={"Authorizations"},
     *     security={{"bearerAuth":{}}},
     *     summary="Delete authorization",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Authorization deleted successfully"),
     *     @OA\Response(response=404, description="Authorization not found")
     * )
     */
    public function delete($id): ResponseInterface
    {
        try {
            $authorization = $this->unitOfWork->Authorizations()->getById((int)$id);
            if (!$authorization) {
                return $this->notFound('Authorization not found');
            }

            $this->unitOfWork->Authorizations()->remove($authorization);
            $this->unitOfWork->saveChanges();

            return $this->success(null, 'Authorization deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
