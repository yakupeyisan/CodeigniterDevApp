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

