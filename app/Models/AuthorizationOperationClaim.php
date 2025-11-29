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

