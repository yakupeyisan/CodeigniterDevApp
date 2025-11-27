<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\EntityFramework\ApplicationDbContext;
use App\Repositories\ApplicationUnitOfWork;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * BaseApiController - Tüm API controller'ları için base class
 * Swagger annotations ve ortak metodlar içerir
 */
abstract class BaseApiController extends BaseController
{
    protected ApplicationDbContext $context;
    protected ApplicationUnitOfWork $unitOfWork;

    public function __construct()
    {
        $this->context = new ApplicationDbContext();
        $this->unitOfWork = new ApplicationUnitOfWork($this->context);
    }

    /**
     * Başarılı response döndür
     */
    protected function success($data = null, string $message = 'Success', int $statusCode = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON([
                'success' => true,
                'message' => $message,
                'data' => $data,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
    }

    /**
     * Hata response döndür
     */
    protected function error(string $message = 'Error', int $statusCode = 400, array $errors = []): ResponseInterface
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON($response);
    }

    /**
     * Validation hatası döndür
     */
    protected function validationError(array $errors): ResponseInterface
    {
        return $this->error('Validation failed', 422, $errors);
    }

    /**
     * Not found response döndür
     */
    protected function notFound(string $message = 'Resource not found'): ResponseInterface
    {
        return $this->error($message, 404);
    }

    /**
     * Entity'yi array'e dönüştür (recursive)
     */
    protected function entityToArray($entity, array $exclude = []): array
    {
        if ($entity === null) {
            return null;
        }

        if (is_array($entity)) {
            return array_map(fn($item) => $this->entityToArray($item, $exclude), $entity);
        }

        if (!is_object($entity)) {
            return $entity;
        }

        // Entity Framework internal properties to exclude
        $internalProperties = [
            'entityState',
            'originalValues',
            'currentValues',
            'navigationProperties',
            'isTracking'
        ];

        $reflection = new \ReflectionClass($entity);
        $result = [];

        foreach ($reflection->getProperties() as $property) {
            $propertyName = $property->getName();
            
            // Skip static properties, excluded properties, and internal Entity Framework properties
            if ($property->isStatic() || 
                in_array($propertyName, $exclude) || 
                in_array($propertyName, $internalProperties)) {
                continue;
            }

            $property->setAccessible(true);
            $value = $property->getValue($entity);

            // DateTime handling
            if ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d H:i:s');
            }

            // Recursive for objects
            if (is_object($value) && !($value instanceof \DateTime)) {
                $value = $this->entityToArray($value, $exclude);
            }

            // Array handling
            if (is_array($value)) {
                $value = array_map(function($item) use ($exclude) {
                    return is_object($item) ? $this->entityToArray($item, $exclude) : $item;
                }, $value);
            }

            $result[$propertyName] = $value;
        }

        return $result;
    }

    /**
     * Request body'den entity oluştur
     */
    protected function createEntityFromRequest(string $entityClass, array $data): object
    {
        $entity = new $entityClass();
        $reflection = new \ReflectionClass($entity);

        foreach ($data as $key => $value) {
            if ($reflection->hasProperty($key)) {
                $property = $reflection->getProperty($key);
                $property->setAccessible(true);
                
                // Type conversion
                $type = $property->getType();
                if ($type instanceof \ReflectionNamedType) {
                    $typeName = $type->getName();
                    if ($typeName === 'int' && $value !== null) {
                        $value = (int)$value;
                    } elseif ($typeName === 'float' && $value !== null) {
                        $value = (float)$value;
                    } elseif ($typeName === 'bool' && $value !== null) {
                        $value = (bool)$value;
                    }
                }
                
                $property->setValue($entity, $value);
            }
        }

        return $entity;
    }

    /**
     * Pagination response
     */
    protected function paginatedResponse(array $data, int $page, int $perPage, int $total): ResponseInterface
    {
        return $this->success([
            'items' => $data,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ]
        ]);
    }
}

