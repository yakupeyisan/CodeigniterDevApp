<?php

namespace App\Models;

use CodeIgniter\Model;

class UserAuthorization
{
    public int $Id;
    public int $UserId;
    public int $AuthorizationId;

    /** @var User $User */
    public User $User;
    /** @var Authorization $Authorization */
    public Authorization $Authorization;

    public function __construct($userId, $authorizationId)
    {
        $this->UserId = $userId;
        $this->AuthorizationId = $authorizationId;
    }
}

