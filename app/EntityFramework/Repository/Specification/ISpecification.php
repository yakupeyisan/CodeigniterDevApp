<?php

namespace App\EntityFramework\Repository\Specification;

use App\EntityFramework\Query\IQueryable;

/**
 * ISpecification interface - Specification pattern
 * Equivalent to ISpecification<T> in .NET
 */
interface ISpecification
{
    /**
     * Apply specification to query
     */
    public function apply(IQueryable $query): IQueryable;

    /**
     * Check if specification is satisfied
     */
    public function isSatisfiedBy(object $entity): bool;

    /**
     * Combine with AND
     */
    public function and(ISpecification $specification): ISpecification;

    /**
     * Combine with OR
     */
    public function or(ISpecification $specification): ISpecification;

    /**
     * Negate specification
     */
    public function not(): ISpecification;
}

