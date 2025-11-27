<?php

namespace App\Controllers\Api;

use App\Models\Department;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * @OA\Tag(
 *     name="Departments",
 *     description="Department management endpoints"
 * )
 */
class DepartmentsController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/Departments",
     *     tags={"Departments"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get list with filtering",
     *     @OA\Response(response=200, description="List of departments")
     * )
     */
    public function index(): ResponseInterface
    {
        try {
            $departments = $this->unitOfWork->Departments()->getAllOrderedByName();
            return $this->success($this->entityToArray($departments));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/Departments/{id}",
     *     tags={"Departments"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get department by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Department details"),
     *     @OA\Response(response=404, description="Department not found")
     * )
     */
    public function show($id): ResponseInterface
    {
        try {
            $department = $this->unitOfWork->Departments()->getById((int)$id);
            if (!$department) {
                return $this->notFound('Department not found');
            }
            return $this->success($this->entityToArray($department));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/Departments",
     *     tags={"Departments"},
     *     security={{"bearerAuth":{}}},
     *     summary="Create new department",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Name", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Department created successfully"),
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

            $department = $this->createEntityFromRequest(Department::class, $data);
            $department->CreatedAt = new \DateTime();

            $this->unitOfWork->Departments()->add($department);
            $this->unitOfWork->saveChanges();

            return $this->success($this->entityToArray($department), 'Department created successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/Departments/{id}",
     *     tags={"Departments"},
     *     security={{"bearerAuth":{}}},
     *     summary="Update department",
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
     *             @OA\Property(property="Name", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Department updated successfully"),
     *     @OA\Response(response=404, description="Department not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update($id): ResponseInterface
    {
        try {
            $department = $this->unitOfWork->Departments()->getById((int)$id);
            if (!$department) {
                return $this->notFound('Department not found');
            }

            $data = $this->request->getJSON(true);
            foreach ($data as $key => $value) {
                if (property_exists($department, $key) && $key !== 'Id') {
                    $department->$key = $value;
                }
            }
            $department->UpdatedAt = new \DateTime();

            $this->unitOfWork->Departments()->update($department);
            $this->unitOfWork->saveChanges();

            return $this->success($this->entityToArray($department), 'Department updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/Departments/{id}",
     *     tags={"Departments"},
     *     security={{"bearerAuth":{}}},
     *     summary="Delete department",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Department deleted successfully"),
     *     @OA\Response(response=404, description="Department not found")
     * )
     */
    public function delete($id): ResponseInterface
    {
        try {
            $department = $this->unitOfWork->Departments()->getById((int)$id);
            if (!$department) {
                return $this->notFound('Department not found');
            }

            $this->unitOfWork->Departments()->remove($department);
            $this->unitOfWork->saveChanges();

            return $this->success(null, 'Department deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
