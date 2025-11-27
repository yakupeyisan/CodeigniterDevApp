<?php

namespace App\EntityFramework\Attributes;

use Attribute;

/**
 * ForeignKey attribute - Specifies foreign key property
 * Equivalent to [ForeignKey("NavigationProperty")] in EF Core
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ForeignKey
{
    public function __construct(
        public string $navigationProperty
    ) {}
}

