<?php

namespace App\EntityFramework\Attributes;

use Attribute;

/**
 * Timestamp attribute - Marks property as concurrency token (RowVersion)
 * Equivalent to [Timestamp] in EF Core
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Timestamp
{
    public function __construct() {}
}

