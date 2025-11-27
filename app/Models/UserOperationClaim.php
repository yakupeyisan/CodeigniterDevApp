<?php

namespace App\Models;

class UserOperationClaim
{
    public int $Id;
    public int $UserId;
    public int $OperationClaimId;

    /** @var User $User */
    public User $User;
    /** @var OperationClaim $OperationClaim */
    public OperationClaim $OperationClaim;

    public function __construct($userId, $operationClaimId)
    {
        $this->UserId = $userId;
        $this->OperationClaimId = $operationClaimId;
    }
}




