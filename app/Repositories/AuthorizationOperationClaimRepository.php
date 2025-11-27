<?php

namespace App\Repositories;

use App\EntityFramework\Repository\Repository;
use App\EntityFramework\Core\DbContext;
use App\Models\AuthorizationOperationClaim;
use App\EntityFramework\Query\IQueryable;

/**
 * AuthorizationOperationClaimRepository - AuthorizationOperationClaim entity için özel repository
 * Many-to-Many join entity için repository
 */
class AuthorizationOperationClaimRepository extends Repository
{
    public function __construct(DbContext $context)
    {
        parent::__construct($context, AuthorizationOperationClaim::class);
    }

    /**
     * Yetkiye göre operasyon yetkilerini getir
     */
    public function getByAuthorizationId(int $authorizationId): array
    {
        return $this->getAll()
            ->where(fn($aoc) => $aoc->AuthorizationId === $authorizationId)
            ->include('OperationClaim')
            ->toList();
    }

    /**
     * Operasyon yetkisine göre yetkileri getir
     */
    public function getByOperationClaimId(int $operationClaimId): array
    {
        return $this->getAll()
            ->where(fn($aoc) => $aoc->OperationClaimId === $operationClaimId)
            ->include('Authorization')
            ->toList();
    }

    /**
     * Yetki ve operasyon yetkisi kombinasyonunu getir
     */
    public function getByAuthorizationAndOperationClaim(int $authorizationId, int $operationClaimId): ?AuthorizationOperationClaim
    {
        return $this->getAll()
            ->where(fn($aoc) => 
                $aoc->AuthorizationId === $authorizationId && 
                $aoc->OperationClaimId === $operationClaimId
            )
            ->firstOrDefault();
    }

    /**
     * Yetkinin belirli bir operasyon yetkisi var mı kontrol et
     */
    public function authorizationHasOperationClaim(int $authorizationId, int $operationClaimId): bool
    {
        return $this->getByAuthorizationAndOperationClaim($authorizationId, $operationClaimId) !== null;
    }

    /**
     * Yetkiye operasyon yetkisi ekle
     */
    public function addOperationClaimToAuthorization(int $authorizationId, int $operationClaimId): bool
    {
        if ($this->authorizationHasOperationClaim($authorizationId, $operationClaimId)) {
            return false; // Zaten var
        }

        $authorizationOperationClaim = new AuthorizationOperationClaim($authorizationId, $operationClaimId);
        $this->add($authorizationOperationClaim);
        return true;
    }

    /**
     * Yetkiden operasyon yetkisini kaldır
     */
    public function removeOperationClaimFromAuthorization(int $authorizationId, int $operationClaimId): bool
    {
        $authorizationOperationClaim = $this->getByAuthorizationAndOperationClaim($authorizationId, $operationClaimId);
        if ($authorizationOperationClaim) {
            $this->remove($authorizationOperationClaim);
            return true;
        }
        return false;
    }

    /**
     * Yetkinin tüm operasyon yetkilerini kaldır
     */
    public function removeAllOperationClaimsFromAuthorization(int $authorizationId): int
    {
        $authorizationOperationClaims = $this->getByAuthorizationId($authorizationId);
        $count = count($authorizationOperationClaims);
        
        foreach ($authorizationOperationClaims as $authorizationOperationClaim) {
            $this->remove($authorizationOperationClaim);
        }
        
        return $count;
    }

    /**
     * Operasyon yetkisinin yetki sayısını getir
     */
    public function getAuthorizationCountByOperationClaim(int $operationClaimId): int
    {
        return $this->getAll()
            ->where(fn($aoc) => $aoc->OperationClaimId === $operationClaimId)
            ->count();
    }
}

