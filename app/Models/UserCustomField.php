<?php

namespace App\Models;


class UserCustomField
{
    public int $Id;
    public int $UserId;
    public string $CustomField01;
    public string $CustomField02;
    public string $CustomField03;
    public string $CustomField04;
    public string $CustomField05;

    /** @var User $User */
    public User $User;

    public function __construct($userId)
    {
        $this->UserId = $userId;
    }
}