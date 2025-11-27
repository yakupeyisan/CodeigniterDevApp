<?php

namespace App\Repositories;

use App\EntityFramework\Repository\Repository;
use App\EntityFramework\Core\DbContext;
use App\Models\UserOperationClaim;
use App\EntityFramework\Query\IQueryable;

/**
 * UserOperationClaimRepository - UserOperationClaim entity için özel repository
 * Many-to-Many join entity için repository
 */
class UserOperationClaimRepository extends Repository
{
    public function __construct(DbContext $context)
    {
        parent::__construct($context, UserOperationClaim::class);
    }

    /**
     * Kullanıcıya göre operasyon yetkilerini getir
     */
    public function getByUserId(int $userId): array
    {
        return $this->getAll()
            ->where(fn($uoc) => $uoc->UserId === $userId)
            ->include('OperationClaim')
            ->toList();
    }

    /**
     * Operasyon yetkisine göre kullanıcıları getir
     */
    public function getByOperationClaimId(int $operationClaimId): array
    {
        return $this->getAll()
            ->where(fn($uoc) => $uoc->OperationClaimId === $operationClaimId)
            ->include('User')
            ->toList();
    }

    /**
     * Kullanıcı ve operasyon yetkisi kombinasyonunu getir
     */
    public function getByUserAndOperationClaim(int $userId, int $operationClaimId): ?UserOperationClaim
    {
        return $this->getAll()
            ->where(fn($uoc) => 
                $uoc->UserId === $userId && 
                $uoc->OperationClaimId === $operationClaimId
            )
            ->firstOrDefault();
    }

    /**
     * Kullanıcının belirli bir operasyon yetkisi var mı kontrol et
     */
    public function userHasOperationClaim(int $userId, int $operationClaimId): bool
    {
        return $this->getByUserAndOperationClaim($userId, $operationClaimId) !== null;
    }

    /**
     * Kullanıcıya operasyon yetkisi ekle
     */
    public function addOperationClaimToUser(int $userId, int $operationClaimId): bool
    {
        if ($this->userHasOperationClaim($userId, $operationClaimId)) {
            return false; // Zaten var
        }

        $userOperationClaim = new UserOperationClaim($userId, $operationClaimId);
        $this->add($userOperationClaim);
        return true;
    }

    /**
     * Kullanıcıdan operasyon yetkisini kaldır
     */
    public function removeOperationClaimFromUser(int $userId, int $operationClaimId): bool
    {
        $userOperationClaim = $this->getByUserAndOperationClaim($userId, $operationClaimId);
        if ($userOperationClaim) {
            $this->remove($userOperationClaim);
            return true;
        }
        return false;
    }

    /**
     * Kullanıcının tüm operasyon yetkilerini kaldır
     */
    public function removeAllOperationClaimsFromUser(int $userId): int
    {
        $userOperationClaims = $this->getByUserId($userId);
        $count = count($userOperationClaims);
        
        foreach ($userOperationClaims as $userOperationClaim) {
            $this->remove($userOperationClaim);
        }
        
        return $count;
    }

    /**
     * Operasyon yetkisinin kullanıcı sayısını getir
     */
    public function getUserCountByOperationClaim(int $operationClaimId): int
    {
        return $this->getAll()
            ->where(fn($uoc) => $uoc->OperationClaimId === $operationClaimId)
            ->count();
    }
}

