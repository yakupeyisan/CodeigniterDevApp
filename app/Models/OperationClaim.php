<?php

namespace App\Models;

use App\EntityFramework\Core\Entity;
use App\EntityFramework\Attributes\Table;
use App\EntityFramework\Attributes\Key;
use App\EntityFramework\Attributes\DatabaseGenerated;
use App\EntityFramework\Attributes\Column;
use App\EntityFramework\Attributes\Required;
use App\EntityFramework\Attributes\MaxLength;
use App\EntityFramework\Attributes\Index;
use App\EntityFramework\Attributes\AuditFields;

/**
 * OperationClaim Entity
 * EF Core compatible entity
 */
#[Table("OperationClaims")]
#[Index("Name", isUnique: true)]
#[AuditFields(createdAt: true, updatedAt: true)]
class OperationClaim extends Entity
{
    #[Key]
    #[DatabaseGenerated(DatabaseGenerated::IDENTITY)]
    #[Column("Id", "INT")]
    public int $Id;

    #[Required]
    #[MaxLength(255)]
    #[Column("Name", "VARCHAR(255)")]
    public string $Name;

    #[MaxLength(500)]
    #[Column("Description", "VARCHAR(500)")]
    public string $Description;

    // Audit fields
    #[Column("CreatedAt", "DATETIME")]
    public ?\DateTime $CreatedAt = null;

    #[Column("UpdatedAt", "DATETIME")]
    public ?\DateTime $UpdatedAt = null;
}

