<?php

namespace App\Models;

use App\EntityFramework\Core\Entity;
use App\EntityFramework\Attributes\Table;
use App\EntityFramework\Attributes\Key;
use App\EntityFramework\Attributes\DatabaseGenerated;
use App\EntityFramework\Attributes\Column;
use App\EntityFramework\Attributes\Required;
use App\EntityFramework\Attributes\MaxLength;
use App\EntityFramework\Attributes\ForeignKey;
use App\EntityFramework\Attributes\InverseProperty;
use App\EntityFramework\Attributes\Index;
use App\EntityFramework\Attributes\AuditFields;

/**
 * User Entity
 * EF Core compatible entity with full annotations and relationships
 */
#[Table("Users")]
#[Index("CompanyId")]
#[AuditFields(createdAt: true, updatedAt: true, deletedAt: true)]
class User extends Entity
{
    #[Key]
    #[DatabaseGenerated(DatabaseGenerated::IDENTITY)]
    #[Column("Id", "INT")]
    public int $Id;

    #[Required]
    #[ForeignKey("Company")]
    #[Column("CompanyId", "INT")]
    public int $CompanyId;

    #[Required]
    #[MaxLength(100)]
    #[Column("FirstName", "VARCHAR(100)")]
    public string $FirstName;

    #[Required]
    #[MaxLength(100)]
    #[Column("LastName", "VARCHAR(100)")]
    public string $LastName;

    /** @var Company */
    #[InverseProperty("Users")]
    public ?Company $Company = null;

    /** @var UserCustomField */
    #[InverseProperty("User")]
    public ?UserCustomField $CustomField = null;

    /** @var UserDepartment[] */
    #[InverseProperty("User")]
    public array $UserDepartments = [];

    /** @var UserAuthorization[] */
    #[InverseProperty("User")]
    public array $UserAuthorizations = [];

    /** @var UserOperationClaim[] */
    #[InverseProperty("User")]
    public array $UserOperationClaims = [];
}

