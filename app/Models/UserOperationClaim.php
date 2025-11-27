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




