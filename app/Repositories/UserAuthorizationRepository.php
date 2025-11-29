<?php

namespace App\Repositories;

use Yakupeyisan\CodeIgniter4\EntityFramework\Repository\Repository;
use Yakupeyisan\CodeIgniter4\EntityFramework\Core\DbContext;
use App\Models\UserAuthorization;
use Yakupeyisan\CodeIgniter4\EntityFramework\Query\IQueryable;

/**
 * UserAuthorizationRepository - UserAuthorization entity için özel repository
 * Many-to-Many join entity için repository
 */
class UserAuthorizationRepository extends Repository
{
    public function __construct(DbContext $context)
    {
        parent::__construct($context, UserAuthorization::class);
    }

    /**
     * Kullanıcıya göre yetkileri getir
     */
    public function getByUserId(int $userId): array
    {
        return $this->getAll()
            ->where(fn($ua) => $ua->UserId === $userId)
            ->include('Authorization')
            ->toList();
    }

    /**
     * Yetkiye göre kullanıcıları getir
     */
    public function getByAuthorizationId(int $authorizationId): array
    {
        return $this->getAll()
            ->where(fn($ua) => $ua->AuthorizationId === $authorizationId)
            ->include('User')
            ->toList();
    }

    /**
     * Kullanıcı ve yetki kombinasyonunu getir
     */
    public function getByUserAndAuthorization(int $userId, int $authorizationId): ?UserAuthorization
    {
        return $this->getAll()
            ->where(fn($ua) => 
                $ua->UserId === $userId && 
                $ua->AuthorizationId === $authorizationId
            )
            ->firstOrDefault();
    }

    /**
     * Kullanıcının belirli bir yetkisi var mı kontrol et
     */
    public function userHasAuthorization(int $userId, int $authorizationId): bool
    {
        return $this->getByUserAndAuthorization($userId, $authorizationId) !== null;
    }

    /**
     * Kullanıcıya yetki ekle
     */
    public function addAuthorizationToUser(int $userId, int $authorizationId): bool
    {
        if ($this->userHasAuthorization($userId, $authorizationId)) {
            return false; // Zaten var
        }

        $userAuthorization = new UserAuthorization($userId, $authorizationId);
        $this->add($userAuthorization);
        return true;
    }

    /**
     * Kullanıcıdan yetkiyi kaldır
     */
    public function removeAuthorizationFromUser(int $userId, int $authorizationId): bool
    {
        $userAuthorization = $this->getByUserAndAuthorization($userId, $authorizationId);
        if ($userAuthorization) {
            $this->remove($userAuthorization);
            return true;
        }
        return false;
    }

    /**
     * Kullanıcının tüm yetkilerini kaldır
     */
    public function removeAllAuthorizationsFromUser(int $userId): int
    {
        $userAuthorizations = $this->getByUserId($userId);
        $count = count($userAuthorizations);
        
        foreach ($userAuthorizations as $userAuthorization) {
            $this->remove($userAuthorization);
        }
        
        return $count;
    }

    /**
     * Yetkinin kullanıcı sayısını getir
     */
    public function getUserCountByAuthorization(int $authorizationId): int
    {
        return $this->getAll()
            ->where(fn($ua) => $ua->AuthorizationId === $authorizationId)
            ->count();
    }
}

