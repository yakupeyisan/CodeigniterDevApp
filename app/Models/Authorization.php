<?php

namespace App\Models;

class Authorization
{
    public int $Id;
    public string $Name;
    public string $Description;

    /** @var AuthorizationOperationClaim[] */
    public array $OperationClaims = [];
}

