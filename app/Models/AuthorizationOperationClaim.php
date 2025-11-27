<?php

namespace App\Models;


class AuthorizationOperationClaim
{
    public int $Id;
    public int $AuthorizationId;
    public int $OperationClaimId;
    /** @var Authorization $Authorization */
    public Authorization $Authorization;
    /** @var OperationClaim $OperationClaim */
    public OperationClaim $OperationClaim;

    public function __construct($authorizationId, $operationClaimId)
    {
        $this->AuthorizationId = $authorizationId;
        $this->OperationClaimId = $operationClaimId;
    }
}

