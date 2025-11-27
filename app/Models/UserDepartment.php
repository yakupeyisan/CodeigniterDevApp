<?php

namespace App\Models;

class UserDepartment
{
    public int $Id;
    public int $UserId;
    public int $DepartmentId;

    /** @var User $User */
    public User $User;
    /** @var Department $Department */
    public Department $Department;

    public function __construct($userId, $departmentId)
    {
        $this->UserId = $userId;
        $this->DepartmentId = $departmentId;
    }
}
