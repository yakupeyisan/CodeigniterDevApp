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
 * AuthorizationOperationClaim Entity
 * Join entity for many-to-many relationship between Authorization and OperationClaim
 * EF Core compatible entity
 */
#[Table("AuthorizationOperationClaims")]
#[Index(["AuthorizationId", "OperationClaimId"], isUnique: true)]
class AuthorizationOperationClaim extends Entity
{
    #[Key]
    #[DatabaseGenerated(DatabaseGenerated::IDENTITY)]
    #[Column("Id", "INT")]
    public int $Id;

    #[Required]
    #[ForeignKey("Authorization")]
    #[Column("AuthorizationId", "INT")]
    public int $AuthorizationId;

    #[Required]
    #[ForeignKey("OperationClaim")]
    #[Column("OperationClaimId", "INT")]
    public int $OperationClaimId;

    /** @var Authorization $Authorization */
    #[InverseProperty("OperationClaims")]
    public ?Authorization $Authorization = null;

    /** @var OperationClaim $OperationClaim */
    #[InverseProperty("AuthorizationOperationClaims")]
    public ?OperationClaim $OperationClaim = null;

    public function __construct($authorizationId = null, $operationClaimId = null)
    {
        if ($authorizationId !== null) {
            $this->AuthorizationId = $authorizationId;
        }
        if ($operationClaimId !== null) {
            $this->OperationClaimId = $operationClaimId;
        }
    }
}

