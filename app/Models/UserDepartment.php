<?php

namespace App\Models;

use Yakupeyisan\CodeIgniter4\EntityFramework\Core\Entity;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Table;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Key;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\DatabaseGenerated;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Column;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Required;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\ForeignKey;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\InverseProperty;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Index;

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
