<?php

namespace App\Repositories;

use Yakupeyisan\CodeIgniter4\EntityFramework\Repository\Repository;
use Yakupeyisan\CodeIgniter4\EntityFramework\Core\DbContext;
use App\Models\Department;
use Yakupeyisan\CodeIgniter4\EntityFramework\Query\IQueryable;

/**
 * DepartmentRepository - Department entity için özel repository
 */
class DepartmentRepository extends Repository
{
    public function __construct(DbContext $context)
    {
        parent::__construct($context, Department::class);
    }

    /**
     * İsme göre departman getir
     */
    public function getByName(string $name): ?Department
    {
        return $this->getAll()
            ->where(fn($d) => $d->Name === $name)
            ->firstOrDefault();
    }

    /**
     * İsime göre departman ara
     */
    public function searchByName(string $searchTerm): array
    {
        return $this->getAll()
            ->where(fn($d) => str_contains($d->Name, $searchTerm))
            ->toList();
    }

    /**
     * Kullanıcılarıyla birlikte departmanı getir
     */
    public function getWithUsers(int $departmentId): ?Department
    {
        return $this->getAll()
            ->where(fn($d) => $d->Id === $departmentId)
            ->include('UserDepartments')
                ->thenInclude('User')
            ->firstOrDefault();
    }

    /**
     * Departman var mı kontrol et
     */
    public function existsByName(string $name): bool
    {
        return $this->getByName($name) !== null;
    }

    /**
     * Departmandaki kullanıcı sayısını getir
     */
    public function getUserCount(int $departmentId): int
    {
        $department = $this->getWithUsers($departmentId);
        if ($department && !empty($department->UserDepartments)) {
            return count($department->UserDepartments);
        }
        return 0;
    }

    /**
     * İsime göre sıralı departmanları getir
     */
    public function getAllOrderedByName(): array
    {
        return $this->getAll()
            ->orderBy(fn($d) => $d->Name)
            ->toList();
    }
}

