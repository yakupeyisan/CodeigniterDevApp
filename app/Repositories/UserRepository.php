<?php

namespace App\Repositories;

use Yakupeyisan\CodeIgniter4\EntityFramework\Repository\Repository;
use Yakupeyisan\CodeIgniter4\EntityFramework\Core\DbContext;
use App\Models\User;
use Yakupeyisan\CodeIgniter4\EntityFramework\Query\IQueryable;

/**
 * UserRepository - User entity için özel repository
 */
class UserRepository extends Repository
{
    public function __construct(DbContext $context)
    {
        parent::__construct($context, User::class);
    }

    /**
     * Company'ye göre kullanıcıları getir
     */
    public function getByCompanyId(int $companyId): IQueryable
    {
        return $this->getAll()
            ->where(fn($u) => $u->CompanyId === $companyId);
    }

    /**
     * İsim ve soyisime göre kullanıcıları getir
     */
    public function getByName(string $firstName, string $lastName): array
    {
        return $this->getAll()
            ->where(fn($u) => $u->FirstName === $firstName && $u->LastName === $lastName)
            ->toList();
    }

    /**
     * İsim veya soyisime göre arama yap
     */
    public function searchByName(string $searchTerm): array
    {
        return $this->getAll()
            ->where(fn($u) => 
                str_contains($u->FirstName, $searchTerm) || 
                str_contains($u->LastName, $searchTerm)
            )
            ->toList();
    }

    /**
     * Company ile birlikte kullanıcıları getir
     */
    public function getWithCompany(): IQueryable
    {
        return $this->getAll()->include('Company');
    }

    /**
     * Tüm ilişkileriyle birlikte kullanıcıyı getir
     */
    public function getWithAllRelations(int $userId): ?User
    {
        return $this->getAll()
            ->where(fn($u) => $u->Id === $userId)
            ->include('Company')
            ->include('CustomField')
            ->include('UserDepartments')
                ->thenInclude('Department')
            ->include('UserAuthorizations')
                ->thenInclude('Authorization')
            ->include('UserOperationClaims')
                ->thenInclude('OperationClaim')
            ->firstOrDefault();
    }

    /**
     * Soft delete edilmemiş kullanıcıları getir
     */
    public function getActiveUsers(): IQueryable
    {
        return $this->getAll()
            ->where(fn($u) => $u->DeletedAt === null);
    }

    /**
     * Company'deki aktif kullanıcı sayısını getir
     */
    public function getActiveUserCountByCompany(int $companyId): int
    {
        return $this->getByCompanyId($companyId)
            ->where(fn($u) => $u->DeletedAt === null)
            ->count();
    }

    /**
     * Son eklenen kullanıcıları getir
     */
    public function getRecentUsers(int $limit = 10): array
    {
        return $this->getAll()
            ->where(fn($u) => $u->DeletedAt === null)
            ->orderBy(fn($u) => $u->CreatedAt)
            ->take($limit)
            ->toList();
    }

    /**
     * Kullanıcıya departman ekle
     */
    public function addDepartment(int $userId, int $departmentId): void
    {
        $userDepartment = new \App\Models\UserDepartment($userId, $departmentId);
        $this->context->add($userDepartment);
    }

    /**
     * Kullanıcıya yetki ekle
     */
    public function addAuthorization(int $userId, int $authorizationId): void
    {
        $userAuthorization = new \App\Models\UserAuthorization($userId, $authorizationId);
        $this->context->add($userAuthorization);
    }

    /**
     * Kullanıcıya operasyon yetkisi ekle
     */
    public function addOperationClaim(int $userId, int $operationClaimId): void
    {
        $userOperationClaim = new \App\Models\UserOperationClaim($userId, $operationClaimId);
        $this->context->add($userOperationClaim);
    }

    /**
     * Soft delete yap
     */
    public function softDelete(User $user): void
    {
        $user->DeletedAt = new \DateTime();
        $this->update($user);
    }

    /**
     * Soft delete edilmiş kullanıcıları geri getir
     */
    public function restore(int $userId): bool
    {
        $user = $this->getAll()
            ->where(fn($u) => $u->Id === $userId)
            ->firstOrDefault();
        
        if ($user && $user->DeletedAt !== null) {
            $user->DeletedAt = null;
            $this->update($user);
            return true;
        }
        
        return false;
    }
}

