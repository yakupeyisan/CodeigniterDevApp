<?php

namespace App\Controllers\Api;

use App\Models\Company;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * @OA\Tag(
 *     name="Companies",
 *     description="Company management endpoints"
 * )
 */
class CompaniesController extends BaseApiController
{
    /**
     * @OA\Get(
     *     path="/api/Companies",
     *     tags={"Companies"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get list with filtering",
     *     @OA\Response(response=200, description="List of companies")
     * )
     */
    public function index(): ResponseInterface
    {
        try {
            $companies = $this->unitOfWork->Companies()->getAllOrderedByName();
            return $this->success($this->entityToArray($companies));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/Companies/{id}",
     *     tags={"Companies"},
     *     security={{"bearerAuth":{}}},
     *     summary="Get company by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Company details"),
     *     @OA\Response(response=404, description="Company not found")
     * )
     */
    public function show($id): ResponseInterface
    {
        try {
            $company = $this->unitOfWork->Companies()->getById((int)$id);
            if (!$company) {
                return $this->notFound('Company not found');
            }
            return $this->success($this->entityToArray($company));
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/Companies",
     *     tags={"Companies"},
     *     security={{"bearerAuth":{}}},
     *     summary="Create new company",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Name", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Company created successfully"),
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

            $company = $this->createEntityFromRequest(Company::class, $data);
            $company->CreatedAt = new \DateTime();

            $this->unitOfWork->Companies()->add($company);
            $this->unitOfWork->saveChanges();

            return $this->success($this->entityToArray($company), 'Company created successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/Companies/{id}",
     *     tags={"Companies"},
     *     security={{"bearerAuth":{}}},
     *     summary="Update company",
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
     *     @OA\Response(response=200, description="Company updated successfully"),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update($id): ResponseInterface
    {
        try {
            $company = $this->unitOfWork->Companies()->getById((int)$id);
            if (!$company) {
                return $this->notFound('Company not found');
            }

            $data = $this->request->getJSON(true);
            foreach ($data as $key => $value) {
                if (property_exists($company, $key) && $key !== 'Id') {
                    $company->$key = $value;
                }
            }
            $company->UpdatedAt = new \DateTime();

            $this->unitOfWork->Companies()->update($company);
            $this->unitOfWork->saveChanges();

            return $this->success($this->entityToArray($company), 'Company updated successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/Companies/{id}",
     *     tags={"Companies"},
     *     security={{"bearerAuth":{}}},
     *     summary="Delete company",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Company deleted successfully"),
     *     @OA\Response(response=404, description="Company not found")
     * )
     */
    public function delete($id): ResponseInterface
    {
        try {
            $company = $this->unitOfWork->Companies()->getById((int)$id);
            if (!$company) {
                return $this->notFound('Company not found');
            }

            $this->unitOfWork->Companies()->remove($company);
            $this->unitOfWork->saveChanges();

            return $this->success(null, 'Company deleted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
