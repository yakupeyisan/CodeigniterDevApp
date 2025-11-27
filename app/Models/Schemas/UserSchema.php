<?php

namespace App\Models\Schemas;

use OpenApi\Attributes\Schema;

#[Schema(
    schema: 'User',
    type: 'object',
    properties: [
        'Id' => ['type' => 'integer'],
        'CompanyId' => ['type' => 'integer'],
        'FirstName' => ['type' => 'string'],
        'LastName' => ['type' => 'string'],
        'CreatedAt' => ['type' => 'string', 'format' => 'date-time'],
        'UpdatedAt' => ['type' => 'string', 'format' => 'date-time'],
        'DeletedAt' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true]
    ]
)]
class UserSchema
{
}

