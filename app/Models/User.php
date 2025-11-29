<?php

namespace App\Models;

use Yakupeyisan\CodeIgniter4\EntityFramework\Core\Entity;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Table;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Key;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\DatabaseGenerated;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Column;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Required;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\MaxLength;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\ForeignKey;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\InverseProperty;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Index;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\AuditFields;

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

