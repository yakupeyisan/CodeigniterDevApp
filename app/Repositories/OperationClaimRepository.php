<?php

namespace App\Repositories;

use App\EntityFramework\Repository\Repository;
use App\EntityFramework\Core\DbContext;
use App\Models\OperationClaim;
use App\EntityFramework\Query\IQueryable;

/**
 * OperationClaimRepository - OperationClaim entity için özel repository
 */
class OperationClaimRepository extends Repository
{
    public function __construct(DbContext $context)
    {
        parent::__construct($context, OperationClaim::class);
    }

    /**
     * İsme göre operasyon yetkisi getir
     */
    public function getByName(string $name): ?OperationClaim
    {
        return $this->getAll()
            ->where(fn($oc) => $oc->Name === $name)
            ->firstOrDefault();
    }

    /**
     * İsime göre operasyon yetkisi ara
     */
    public function searchByName(string $searchTerm): array
    {
        return $this->getAll()
            ->where(fn($oc) => str_contains($oc->Name, $searchTerm))
            ->toList();
    }

    /**
     * Operasyon yetkisi var mı kontrol et
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
            ->where(fn($oc) => str_contains($oc->Description, $searchTerm))
            ->toList();
    }

    /**
     * İsime göre sıralı operasyon yetkilerini getir
     */
    public function getAllOrderedByName(): array
    {
        return $this->getAll()
            ->orderBy(fn($oc) => $oc->Name)
            ->toList();
    }
}

