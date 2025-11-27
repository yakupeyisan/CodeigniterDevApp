<?php

namespace App\Models;

class User
{
    public int $Id;
    public int $CompanyId;
    public string $FirstName;
    public string $LastName;
    /** @var Company */
    public Company $Company;

    /** @var UserCustomField[] */
    public array $CustomFields = [];
    /** @var UserDepartment[] */
    public array $UserDepartments = [];

    /** @var UserAuthorization[] */
    public array $UserAuthorizations = [];

    /** @var UserOperationClaim[] */
    public array $UserOperationClaims = [];

}

