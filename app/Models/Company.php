<?php

namespace App\Models;

use CodeIgniter\Model;

class Company   
{
    public int $Id;
    public string $Name;

    /** @var User[] */
    public array $Users = [];

}

