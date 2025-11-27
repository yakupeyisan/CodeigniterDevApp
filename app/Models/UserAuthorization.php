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
 * UserAuthorization Entity
 * Join entity for many-to-many relationship between User and Authorization
 * EF Core compatible entity
 */
#[Table("UserAuthorizations")]
#[Index(["UserId", "AuthorizationId"], isUnique: true)]
class UserAuthorization extends Entity
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
    #[ForeignKey("Authorization")]
    #[Column("AuthorizationId", "INT")]
    public int $AuthorizationId;

    /** @var User $User */
    #[InverseProperty("UserAuthorizations")]
    public ?User $User = null;

    /** @var Authorization $Authorization */
    #[InverseProperty("UserAuthorizations")]
    public ?Authorization $Authorization = null;

    public function __construct($userId = null, $authorizationId = null)
    {
        if ($userId !== null) {
            $this->UserId = $userId;
        }
        if ($authorizationId !== null) {
            $this->AuthorizationId = $authorizationId;
        }
    }
}

