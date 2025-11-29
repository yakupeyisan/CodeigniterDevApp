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
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\MaxLength;
use Yakupeyisan\CodeIgniter4\EntityFramework\Attributes\Index;

/**
 * UserCustomField Entity
 * One-to-one relationship with User
 * EF Core compatible entity
 */
#[Table("UserCustomFields")]
#[Index("UserId", isUnique: true)]
class UserCustomField extends Entity
{
    #[Key]
    #[DatabaseGenerated(DatabaseGenerated::IDENTITY)]
    #[Column("Id", "INT")]
    public int $Id;

    #[Required]
    #[ForeignKey("User")]
    #[Column("UserId", "INT")]
    public int $UserId;

    #[MaxLength(255)]
    #[Column("CustomField01", "VARCHAR(255)")]
    public ?string $CustomField01 = null;

    #[MaxLength(255)]
    #[Column("CustomField02", "VARCHAR(255)")]
    public ?string $CustomField02 = null;

    #[MaxLength(255)]
    #[Column("CustomField03", "VARCHAR(255)")]
    public ?string $CustomField03 = null;

    #[MaxLength(255)]
    #[Column("CustomField04", "VARCHAR(255)")]
    public ?string $CustomField04 = null;

    #[MaxLength(255)]
    #[Column("CustomField05", "VARCHAR(255)")]
    public ?string $CustomField05 = null;

    /** @var User $User */
    #[InverseProperty("CustomField")]
    public ?User $User = null;

    public function __construct($userId = null)
    {
        if ($userId !== null) {
            $this->UserId = $userId;
        }
    }
}