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
 * UserOperationClaim Entity
 * Join entity for many-to-many relationship between User and OperationClaim
 * EF Core compatible entity
 */
#[Table("UserOperationClaims")]
#[Index(["UserId", "OperationClaimId"], isUnique: true)]
class UserOperationClaim extends Entity
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
    #[ForeignKey("OperationClaim")]
    #[Column("OperationClaimId", "INT")]
    public int $OperationClaimId;

    /** @var User $User */
    #[InverseProperty("UserOperationClaims")]
    public ?User $User = null;

    /** @var OperationClaim $OperationClaim */
    #[InverseProperty("UserOperationClaims")]
    public ?OperationClaim $OperationClaim = null;

    public function __construct($userId = null, $operationClaimId = null)
    {
        if ($userId !== null) {
            $this->UserId = $userId;
        }
        if ($operationClaimId !== null) {
            $this->OperationClaimId = $operationClaimId;
        }
    }
}




