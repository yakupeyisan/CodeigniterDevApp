<?php

namespace App\Models;

use Yakupeyisan\CodeIgniter4\EntityFramework\Core\Entity;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Table;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Key;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\DatabaseGenerated;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Column;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Required;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\MaxLength;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\InverseProperty;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Index;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\AuditFields;

/**
 * Authorization Entity
 * EF Core compatible entity
 */
#[Table("Authorizations")]
#[Index("Name", isUnique: true)]
#[AuditFields(createdAt: true, updatedAt: true)]
class Authorization extends Entity
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

    /** @var AuthorizationOperationClaim[] */
    #[InverseProperty("Authorization")]
    public array $OperationClaims = [];

    /** @var UserAuthorization[] */
    #[InverseProperty("Authorization")]
    public array $UserAuthorizations = [];

}

