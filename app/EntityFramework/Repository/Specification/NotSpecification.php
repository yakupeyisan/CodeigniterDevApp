<?php

namespace App\EntityFramework\Repository\Specification;

use App\EntityFramework\Query\IQueryable;

/**
 * NotSpecification - Negates a specification
 */
class NotSpecification extends Specification
{
    private ISpecification $specification;

    public function __construct(ISpecification $specification)
    {
        $this->specification = $specification;
    }

    public function apply(IQueryable $query): IQueryable
    {
        // Negation in query building is complex
        // This is a placeholder
        return $query;
    }

    public function isSatisfiedBy(object $entity): bool
    {
        return !$this->specification->isSatisfiedBy($entity);
    }
}

