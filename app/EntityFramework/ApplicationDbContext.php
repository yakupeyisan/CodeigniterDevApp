<?php

namespace App\EntityFramework;

use Yakupeyisan\CodeIgniter4\EntityFramework\Core\DbContext;
use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use App\Models\OperationClaim;
use App\Models\UserOperationClaim;
use App\Models\UserDepartment;
use App\Models\UserCustomField;
use App\Models\UserAuthorization;
use App\Models\Authorization;
use App\Models\AuthorizationOperationClaim;

/**
 * ApplicationDbContext - Example DbContext implementation
 * Shows how to configure entities using Fluent API
 */
class ApplicationDbContext extends DbContext
{
    /**
     * Configure entities using Fluent API
     */
    protected function onModelCreating(): void
    {
        // User configuration
        $this->entity(User::class)
            ->hasKey('Id')
            ->toTable('Users')
            ->property('Id')
                ->valueGeneratedOnAdd()
                ->entity()
            ->property('CompanyId')
                ->isRequired()
                ->entity()
            ->property('FirstName')
                ->hasMaxLength(100)
                ->isRequired()
                ->entity()
            ->property('LastName')
                ->hasMaxLength(100)
                ->isRequired()
                ->entity()
            ->hasOne('Company')
                ->hasForeignKey('CompanyId')
                ->withMany('Users')
                ->onDelete('CASCADE')
                ->entity()
            ->hasOne('CustomField')
                ->hasForeignKey('UserId')
                ->withOne('User')
                ->onDelete('CASCADE')
                ->entity()
            ->hasMany('UserDepartments')
                ->hasForeignKey('UserId')
                ->withOne('User')
                ->onDelete('CASCADE')
                ->entity()
            ->hasMany('UserAuthorizations')
                ->hasForeignKey('UserId')
                ->withOne('User')
                ->onDelete('CASCADE')
                ->entity()
            ->hasMany('UserOperationClaims')
                ->hasForeignKey('UserId')
                ->withOne('User')
                ->onDelete('CASCADE')
                ->entity()
            ->hasIndex('CompanyId')
            ->hasSoftDelete('DeletedAt');

        // Company configuration
        $this->entity(Company::class)
            ->hasKey('Id')
            ->toTable('Companies')
            ->property('Id')
                ->valueGeneratedOnAdd()
                ->entity()
            ->property('Name')
                ->hasMaxLength(255)
                ->isRequired()
                ->entity()
            ->hasMany('Users')
                ->hasForeignKey('CompanyId')
                ->withOne('Company')
                ->onDelete('CASCADE')
                ->entity()
            ->hasIndex('Name', null, true);

        // Department configuration
        $this->entity(Department::class)
            ->hasKey('Id')
            ->toTable('Departments')
            ->property('Id')
                ->valueGeneratedOnAdd()
                ->entity()
            ->property('Name')
                ->hasMaxLength(255)
                ->isRequired()
                ->entity()
            ->hasMany('UserDepartments')
                ->hasForeignKey('DepartmentId')
                ->withOne('Department')
                ->onDelete('CASCADE')
                ->entity()
            ->hasIndex('Name', null, true);

        // OperationClaim configuration
        $this->entity(OperationClaim::class)
            ->hasKey('Id')
            ->toTable('OperationClaims')
            ->property('Id')
                ->valueGeneratedOnAdd()
                ->entity()
            ->property('Name')
                ->hasMaxLength(255)
                ->isRequired()
                ->entity()
            ->property('Description')
                ->hasMaxLength(500)
                ->entity()
            ->hasIndex('Name', null, true);

        // UserOperationClaim configuration (Many-to-Many join entity)
        $this->entity(UserOperationClaim::class)
            ->hasKey('Id')
            ->toTable('UserOperationClaims')
            ->property('UserId')
                ->isRequired()
                ->entity()
            ->property('OperationClaimId')
                ->isRequired()
                ->entity()
            ->hasOne('User')
                ->hasForeignKey('UserId')
                ->withMany('UserOperationClaims')
                ->onDelete('CASCADE')
                ->entity()
            ->hasOne('OperationClaim')
                ->hasForeignKey('OperationClaimId')
                ->withMany('UserOperationClaims')
                ->onDelete('CASCADE')
                ->entity()
            ->hasIndex(['UserId', 'OperationClaimId'], null, true);

        // UserDepartment configuration (Many-to-Many join entity)
        $this->entity(UserDepartment::class)
            ->hasKey('Id')
            ->toTable('UserDepartments')
            ->property('UserId')
                ->isRequired()
                ->entity()
            ->property('DepartmentId')
                ->isRequired()
                ->entity()
            ->hasOne('User')
                ->hasForeignKey('UserId')
                ->withMany('UserDepartments')
                ->onDelete('CASCADE')
                ->entity()
            ->hasOne('Department')
                ->hasForeignKey('DepartmentId')
                ->withMany('UserDepartments')
                ->onDelete('CASCADE')
                ->entity()
            ->hasIndex(['UserId', 'DepartmentId'], null, true);

        // UserCustomField configuration (One-to-One)
        $this->entity(UserCustomField::class)
            ->hasKey('Id')
            ->toTable('UserCustomFields')
            ->property('Id')
                ->valueGeneratedOnAdd()
                ->entity()
            ->property('UserId')
                ->isRequired()
                ->entity()
            ->hasOne('User')
                ->hasForeignKey('UserId')
                ->withOne('CustomFields')
                ->onDelete('CASCADE')
                ->entity()
            ->hasIndex('UserId', null, true);

        // UserAuthorization configuration (Many-to-Many join entity)
        $this->entity(UserAuthorization::class)
            ->hasKey('Id')
            ->toTable('UserAuthorizations')
            ->property('UserId')
                ->isRequired()
                ->entity()
            ->property('AuthorizationId')
                ->isRequired()
                ->entity()
            ->hasOne('User')
                ->hasForeignKey('UserId')
                ->withMany('UserAuthorizations')
                ->onDelete('CASCADE')
                ->entity()
            ->hasOne('Authorization')
                ->hasForeignKey('AuthorizationId')
                ->withMany('UserAuthorizations')
                ->onDelete('CASCADE')
                ->entity()
            ->hasIndex(['UserId', 'AuthorizationId'], null, true);

        // Authorization configuration
        $this->entity(Authorization::class)
            ->hasKey('Id')
            ->toTable('Authorizations')
            ->property('Id')
                ->valueGeneratedOnAdd()
                ->entity()
            ->property('Name')
                ->hasMaxLength(255)
                ->isRequired()
                ->entity()
            ->property('Description')
                ->hasMaxLength(500)
                ->entity()
            ->hasMany('OperationClaims')
                ->hasForeignKey('AuthorizationId')
                ->withOne('Authorization')
                ->onDelete('CASCADE')
                ->entity()
            ->hasMany('UserAuthorizations')
                ->hasForeignKey('AuthorizationId')
                ->withOne('Authorization')
                ->onDelete('CASCADE')
                ->entity()
            ->hasIndex('Name', null, true);

        // AuthorizationOperationClaim configuration (Many-to-Many join entity)
        $this->entity(AuthorizationOperationClaim::class)
            ->hasKey('Id')
            ->toTable('AuthorizationOperationClaims')
            ->property('AuthorizationId')
                ->isRequired()
                ->entity()
            ->property('OperationClaimId')
                ->isRequired()
                ->entity()
            ->hasOne('Authorization')
                ->hasForeignKey('AuthorizationId')
                ->withMany('OperationClaims')
                ->onDelete('CASCADE')
                ->entity()
            ->hasOne('OperationClaim')
                ->hasForeignKey('OperationClaimId')
                ->withMany('AuthorizationOperationClaims')
                ->onDelete('CASCADE')
                ->entity()
            ->hasIndex(['AuthorizationId', 'OperationClaimId'], null, true);
    }

    /**
     * DbSet properties (equivalent to DbSet<T> in EF Core)
     */
    public function Users()
    {
        return $this->set(User::class);
    }

    public function Companies()
    {
        return $this->set(Company::class);
    }

    public function Departments()
    {
        return $this->set(Department::class);
    }

    public function OperationClaims()
    {
        return $this->set(OperationClaim::class);
    }

    public function UserOperationClaims()
    {
        return $this->set(UserOperationClaim::class);
    }

    public function UserDepartments()
    {
        return $this->set(UserDepartment::class);
    }

    public function UserCustomFields()
    {
        return $this->set(UserCustomField::class);
    }

    public function UserAuthorizations()
    {
        return $this->set(UserAuthorization::class);
    }

    public function Authorizations()
    {
        return $this->set(Authorization::class);
    }

    public function AuthorizationOperationClaims()
    {
        return $this->set(AuthorizationOperationClaim::class);
    }
}

