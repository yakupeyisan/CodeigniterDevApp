<?php

namespace App\Models;

class Department
{
    public int $Id;
    public string $Name;
    /** @var UserDepartment[] */
    public array $UserDepartments = [];
}

