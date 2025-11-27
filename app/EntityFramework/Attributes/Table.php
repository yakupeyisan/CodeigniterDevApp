<?php

namespace App\EntityFramework\Attributes;

use Attribute;

/**
 * Table attribute - Specifies the database table name
 * Equivalent to [Table("TableName")] in EF Core
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
    public function __construct(
        public string $name,
        public ?string $schema = null
    ) {}
}

