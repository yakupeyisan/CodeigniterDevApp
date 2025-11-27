<?php

namespace App\Controllers\Api;

use App\Models\OperationClaim;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * @OA\Tag(
 *     name="OperationClaims",
 *     description="Operation claim management endpoints"
 * )
 */
class OperationClaimsController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/OperationClaims",
     *     tags={"OperationClaims"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get list with filtering",
     *     @OA\Response(response=200, description="List of operation claims")
     * )
     */
    public function index(): ResponseInterface
    {
        try {
            $operationClaims = $this->unitOfWork->OperationClaims()->getAllOrderedByName();
            return $this->success($this->entityToArray($operationClaims));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/OperationClaims/{id}",
     *     tags={"OperationClaims"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get operation claim by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Operation claim details"),
     *     @OA\Response(response=404, description="Operation claim not found")
     * )
     */
    public function show($id): ResponseInterface
    {
        try {
            $operationClaim = $this->unitOfWork->OperationClaims()->getById((int)$id);
            if (!$operationClaim) {
                return $this->notFound('Operation claim not found');
            }
            return $this->success($this->entityToArray($operationClaim));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/OperationClaims",
     *     tags={"OperationClaims"},
     *     security={{"bearerAuth":{}}},
     *     summary="Create new operation claim",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Name", type="string"),
     *             @OA\Property(property="Description", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Operation claim created successfully"),
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

            $operationClaim = $this->createEntityFromRequest(OperationClaim::class, $data);
            $operationClaim->CreatedAt = new \DateTime();

            $this->unitOfWork->OperationClaims()->add($operationClaim);
            $this->unitOfWork->saveChanges();

            return $this->success($this->entityToArray($operationClaim), 'Operation claim created successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/OperationClaims/{id}",
     *     tags={"OperationClaims"},
     *     security={{"bearerAuth":{}}},
     *     summary="Update operation claim",
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
     *     @OA\Response(response=200, description="Operation claim updated successfully"),
     *     @OA\Response(response=404, description="Operation claim not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update($id): ResponseInterface
    {
        try {
            $operationClaim = $this->unitOfWork->OperationClaims()->getById((int)$id);
            if (!$operationClaim) {
                return $this->notFound('Operation claim not found');
            }

            $data = $this->request->getJSON(true);
            foreach ($data as $key => $value) {
                if (property_exists($operationClaim, $key) && $key !== 'Id') {
                    $operationClaim->$key = $value;
                }
            }
            $operationClaim->UpdatedAt = new \DateTime();

            $this->unitOfWork->OperationClaims()->update($operationClaim);
            $this->unitOfWork->saveChanges();

            return $this->success($this->entityToArray($operationClaim), 'Operation claim updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/OperationClaims/{id}",
     *     tags={"OperationClaims"},
     *     security={{"bearerAuth":{}}},
     *     summary="Delete operation claim",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Operation claim deleted successfully"),
     *     @OA\Response(response=404, description="Operation claim not found")
     * )
     */
    public function delete($id): ResponseInterface
    {
        try {
            $operationClaim = $this->unitOfWork->OperationClaims()->getById((int)$id);
            if (!$operationClaim) {
                return $this->notFound('Operation claim not found');
            }

            $this->unitOfWork->OperationClaims()->remove($operationClaim);
            $this->unitOfWork->saveChanges();

            return $this->success(null, 'Operation claim deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
