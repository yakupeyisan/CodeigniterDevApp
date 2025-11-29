<?php

namespace App\Repositories;

use Yakupeyisan\CodeIgniter4\EntityFramework\Repository\Repository;
use Yakupeyisan\CodeIgniter4\EntityFramework\Core\DbContext;
use App\Models\UserDepartment;
use Yakupeyisan\CodeIgniter4\EntityFramework\Query\IQueryable;

/**
 * UserDepartmentRepository - UserDepartment entity için özel repository
 * Many-to-Many join entity için repository
 */
class UserDepartmentRepository extends Repository
{
    public function __construct(DbContext $context)
    {
        parent::__construct($context, UserDepartment::class);
    }

    /**
     * Kullanıcıya göre departmanları getir
     */
    public function getByUserId(int $userId): array
    {
        return $this->getAll()
            ->where(fn($ud) => $ud->UserId === $userId)
            ->include('Department')
            ->toList();
    }

    /**
     * Departmana göre kullanıcıları getir
     */
    public function getByDepartmentId(int $departmentId): array
    {
        return $this->getAll()
            ->where(fn($ud) => $ud->DepartmentId === $departmentId)
            ->include('User')
            ->toList();
    }

    /**
     * Kullanıcı ve departman kombinasyonunu getir
     */
    public function getByUserAndDepartment(int $userId, int $departmentId): ?UserDepartment
    {
        return $this->getAll()
            ->where(fn($ud) => 
                $ud->UserId === $userId && 
                $ud->DepartmentId === $departmentId
            )
            ->firstOrDefault();
    }

    /**
     * Kullanıcının belirli bir departmanda olup olmadığını kontrol et
     */
    public function userInDepartment(int $userId, int $departmentId): bool
    {
        return $this->getByUserAndDepartment($userId, $departmentId) !== null;
    }

    /**
     * Kullanıcıyı departmana ekle
     */
    public function addUserToDepartment(int $userId, int $departmentId): bool
    {
        if ($this->userInDepartment($userId, $departmentId)) {
            return false; // Zaten var
        }

        $userDepartment = new UserDepartment($userId, $departmentId);
        $this->add($userDepartment);
        return true;
    }

    /**
     * Kullanıcıyı departmandan çıkar
     */
    public function removeUserFromDepartment(int $userId, int $departmentId): bool
    {
        $userDepartment = $this->getByUserAndDepartment($userId, $departmentId);
        if ($userDepartment) {
            $this->remove($userDepartment);
            return true;
        }
        return false;
    }

    /**
     * Kullanıcıyı tüm departmanlardan çıkar
     */
    public function removeUserFromAllDepartments(int $userId): int
    {
        $userDepartments = $this->getByUserId($userId);
        $count = count($userDepartments);
        
        foreach ($userDepartments as $userDepartment) {
            $this->remove($userDepartment);
        }
        
        return $count;
    }

    /**
     * Departmandaki kullanıcı sayısını getir
     */
    public function getUserCountByDepartment(int $departmentId): int
    {
        return $this->getAll()
            ->where(fn($ud) => $ud->DepartmentId === $departmentId)
            ->count();
    }
}

