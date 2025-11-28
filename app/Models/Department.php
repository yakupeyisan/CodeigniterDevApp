<?php

namespace App\Models;

use App\EntityFramework\Core\Entity;
use App\EntityFramework\Attributes\Table;
use App\EntityFramework\Attributes\Key;
use App\EntityFramework\Attributes\DatabaseGenerated;
use App\EntityFramework\Attributes\Column;
use App\EntityFramework\Attributes\Required;
use App\EntityFramework\Attributes\MaxLength;
use App\EntityFramework\Attributes\InverseProperty;
use App\EntityFramework\Attributes\Index;
use App\EntityFramework\Attributes\AuditFields;

/**
 * Department Entity
 * EF Core compatible entity
 */
#[Table("Departments")]
#[Index("Name", isUnique: true)]
#[AuditFields(createdAt: true, updatedAt: true)]
class Department extends Entity
{
    #[Key]
    #[DatabaseGenerated(DatabaseGenerated::IDENTITY)]
    #[Column("Id", "INT")]
    public int $Id;

    #[Required]
    #[MaxLength(255)]
    #[Column("Name", "VARCHAR(255)")]
    public string $Name;

    /** @var UserDepartment[] */
    #[InverseProperty("Department")]
    public array $UserDepartments = [];

}

