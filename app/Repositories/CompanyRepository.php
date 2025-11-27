<?php

namespace App\Repositories;

use App\EntityFramework\Repository\Repository;
use App\EntityFramework\Core\DbContext;
use App\Models\Company;
use App\EntityFramework\Query\IQueryable;

/**
 * CompanyRepository - Company entity için özel repository
 */
class CompanyRepository extends Repository
{
    public function __construct(DbContext $context)
    {
        parent::__construct($context, Company::class);
    }

    /**
     * İsme göre şirket getir
     */
    public function getByName(string $name): ?Company
    {
        return $this->getAll()
            ->where(fn($c) => $c->Name === $name)
            ->firstOrDefault();
    }

    /**
     * İsime göre şirket ara
     */
    public function searchByName(string $searchTerm): array
    {
        return $this->getAll()
            ->where(fn($c) => str_contains($c->Name, $searchTerm))
            ->toList();
    }

    /**
     * Kullanıcılarıyla birlikte şirketi getir
     */
    public function getWithUsers(int $companyId): ?Company
    {
        return $this->getAll()
            ->where(fn($c) => $c->Id === $companyId)
            ->include('Users')
            ->firstOrDefault();
    }

    /**
     * Şirket var mı kontrol et
     */
    public function existsByName(string $name): bool
    {
        return $this->getByName($name) !== null;
    }

    /**
     * Şirketteki kullanıcı sayısını getir
     */
    public function getUserCount(int $companyId): int
    {
        $company = $this->getWithUsers($companyId);
        return $company ? count($company->Users) : 0;
    }

    /**
     * Son eklenen şirketleri getir
     */
    public function getRecentCompanies(int $limit = 10): array
    {
        return $this->getAll()
            ->orderBy(fn($c) => $c->CreatedAt)
            ->take($limit)
            ->toList();
    }

    /**
     * İsime göre sıralı şirketleri getir
     */
    public function getAllOrderedByName(): array
    {
        return $this->getAll()
            ->orderBy(fn($c) => $c->Name)
            ->toList();
    }
}

