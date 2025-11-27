<?php

namespace App\Repositories;

use App\EntityFramework\Repository\Repository;
use App\EntityFramework\Core\DbContext;
use App\Models\Authorization;
use App\EntityFramework\Query\IQueryable;

/**
 * AuthorizationRepository - Authorization entity için özel repository
 */
class AuthorizationRepository extends Repository
{
    public function __construct(DbContext $context)
    {
        parent::__construct($context, Authorization::class);
    }

    /**
     * İsme göre yetki getir
     */
    public function getByName(string $name): ?Authorization
    {
        return $this->getAll()
            ->where(fn($a) => $a->Name === $name)
            ->firstOrDefault();
    }

    /**
     * İsime göre yetki ara
     */
    public function searchByName(string $searchTerm): array
    {
        return $this->getAll()
            ->where(fn($a) => str_contains($a->Name, $searchTerm))
            ->toList();
    }

    /**
     * Operasyon yetkileriyle birlikte yetkiyi getir
     */
    public function getWithOperationClaims(int $authorizationId): ?Authorization
    {
        return $this->getAll()
            ->where(fn($a) => $a->Id === $authorizationId)
            ->include('OperationClaims')
                ->thenInclude('OperationClaim')
            ->firstOrDefault();
    }

    /**
     * Kullanıcılarıyla birlikte yetkiyi getir
     */
    public function getWithUsers(int $authorizationId): ?Authorization
    {
        return $this->getAll()
            ->where(fn($a) => $a->Id === $authorizationId)
            ->include('UserAuthorizations')
                ->thenInclude('User')
            ->firstOrDefault();
    }

    /**
     * Tüm ilişkileriyle birlikte yetkiyi getir
     */
    public function getWithAllRelations(int $authorizationId): ?Authorization
    {
        return $this->getAll()
            ->where(fn($a) => $a->Id === $authorizationId)
            ->include('OperationClaims')
                ->thenInclude('OperationClaim')
            ->include('UserAuthorizations')
                ->thenInclude('User')
            ->firstOrDefault();
    }

    /**
     * Yetki var mı kontrol et
     */
    public function existsByName(string $name): bool
    {
        return $this->getByName($name) !== null;
    }

    /**
     * Açıklamaya göre ara
     */
    public function searchByDescription(string $searchTerm): array
    {
        return $this->getAll()
            ->where(fn($a) => str_contains($a->Description, $searchTerm))
            ->toList();
    }

    /**
     * Yetkideki kullanıcı sayısını getir
     */
    public function getUserCount(int $authorizationId): int
    {
        $authorization = $this->getWithUsers($authorizationId);
        if ($authorization && !empty($authorization->UserAuthorizations)) {
            return count($authorization->UserAuthorizations);
        }
        return 0;
    }

    /**
     * İsime göre sıralı yetkileri getir
     */
    public function getAllOrderedByName(): array
    {
        return $this->getAll()
            ->orderBy(fn($a) => $a->Name)
            ->toList();
    }
}

