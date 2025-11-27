<?php

namespace App\Models;

use App\EntityFramework\Core\Entity;
use App\EntityFramework\Attributes\Table;
use App\EntityFramework\Attributes\Key;
use App\EntityFramework\Attributes\DatabaseGenerated;
use App\EntityFramework\Attributes\Column;
use App\EntityFramework\Attributes\Required;
use App\EntityFramework\Attributes\ForeignKey;
use App\EntityFramework\Attributes\InverseProperty;
use App\EntityFramework\Attributes\Index;

/**
 * UserDepartment Entity
 * Join entity for many-to-many relationship between User and Department
 * EF Core compatible entity
 */
#[Table("UserDepartments")]
#[Index(["UserId", "DepartmentId"], isUnique: true)]
class UserDepartment extends Entity
{
    #[Key]
    #[DatabaseGenerated(DatabaseGenerated::IDENTITY)]
    #[Column("Id", "INT")]
    public int $Id;

    #[Required]
    #[ForeignKey("User")]
    #[Column("UserId", "INT")]
    public int $UserId;

    #[Required]
    #[ForeignKey("Department")]
    #[Column("DepartmentId", "INT")]
    public int $DepartmentId;

    /** @var User $User */
    #[InverseProperty("UserDepartments")]
    public ?User $User = null;

    /** @var Department $Department */
    #[InverseProperty("UserDepartments")]
    public ?Department $Department = null;

    public function __construct($userId = null, $departmentId = null)
    {
        if ($userId !== null) {
            $this->UserId = $userId;
        }
        if ($departmentId !== null) {
            $this->DepartmentId = $departmentId;
        }
    }
}
