<?php

namespace App\Repositories;

use Yakupeyisan\CodeIgniter4\EntityFramework\Core\DbContext;
use Yakupeyisan\CodeIgniter4\EntityFramework\Repository\IUnitOfWork;
use Yakupeyisan\CodeIgniter4\EntityFramework\Repository\IRepository;

/**
 * ApplicationUnitOfWork - Özel repository'leri içeren UnitOfWork
 * Tüm entity'ler için özel repository metodlarına erişim sağlar
 */
class ApplicationUnitOfWork implements IUnitOfWork
{
    protected DbContext $context;
    
    // Özel repository'ler
    protected ?UserRepository $userRepository = null;
    protected ?CompanyRepository $companyRepository = null;
    protected ?DepartmentRepository $departmentRepository = null;
    protected ?OperationClaimRepository $operationClaimRepository = null;
    protected ?UserOperationClaimRepository $userOperationClaimRepository = null;
    protected ?UserDepartmentRepository $userDepartmentRepository = null;
    protected ?UserCustomFieldRepository $userCustomFieldRepository = null;
    protected ?UserAuthorizationRepository $userAuthorizationRepository = null;
    protected ?AuthorizationRepository $authorizationRepository = null;
    protected ?AuthorizationOperationClaimRepository $authorizationOperationClaimRepository = null;

    public function __construct(DbContext $context)
    {
        $this->context = $context;
    }

    /**
     * Generic repository getir (entity type'a göre)
     */
    public function getRepository(string $entityType): IRepository
    {
        // Özel repository'ler için mapping
        $mapping = [
            \App\Models\User::class => fn() => $this->Users(),
            \App\Models\Company::class => fn() => $this->Companies(),
            \App\Models\Department::class => fn() => $this->Departments(),
            \App\Models\OperationClaim::class => fn() => $this->OperationClaims(),
            \App\Models\UserOperationClaim::class => fn() => $this->UserOperationClaims(),
            \App\Models\UserDepartment::class => fn() => $this->UserDepartments(),
            \App\Models\UserCustomField::class => fn() => $this->UserCustomFields(),
            \App\Models\UserAuthorization::class => fn() => $this->UserAuthorizations(),
            \App\Models\Authorization::class => fn() => $this->Authorizations(),
            \App\Models\AuthorizationOperationClaim::class => fn() => $this->AuthorizationOperationClaims(),
        ];

        if (isset($mapping[$entityType])) {
            return $mapping[$entityType]();
        }

        // Generic repository döndür
        return new \Yakupeyisan\CodeIgniter4\EntityFramework\Repository\Repository($this->context, $entityType);
    }

    /**
     * User Repository
     */
    public function Users(): UserRepository
    {
        if ($this->userRepository === null) {
            $this->userRepository = new UserRepository($this->context);
        }
        return $this->userRepository;
    }

    /**
     * Company Repository
     */
    public function Companies(): CompanyRepository
    {
        if ($this->companyRepository === null) {
            $this->companyRepository = new CompanyRepository($this->context);
        }
        return $this->companyRepository;
    }

    /**
     * Department Repository
     */
    public function Departments(): DepartmentRepository
    {
        if ($this->departmentRepository === null) {
            $this->departmentRepository = new DepartmentRepository($this->context);
        }
        return $this->departmentRepository;
    }

    /**
     * OperationClaim Repository
     */
    public function OperationClaims(): OperationClaimRepository
    {
        if ($this->operationClaimRepository === null) {
            $this->operationClaimRepository = new OperationClaimRepository($this->context);
        }
        return $this->operationClaimRepository;
    }

    /**
     * UserOperationClaim Repository
     */
    public function UserOperationClaims(): UserOperationClaimRepository
    {
        if ($this->userOperationClaimRepository === null) {
            $this->userOperationClaimRepository = new UserOperationClaimRepository($this->context);
        }
        return $this->userOperationClaimRepository;
    }

    /**
     * UserDepartment Repository
     */
    public function UserDepartments(): UserDepartmentRepository
    {
        if ($this->userDepartmentRepository === null) {
            $this->userDepartmentRepository = new UserDepartmentRepository($this->context);
        }
        return $this->userDepartmentRepository;
    }

    /**
     * UserCustomField Repository
     */
    public function UserCustomFields(): UserCustomFieldRepository
    {
        if ($this->userCustomFieldRepository === null) {
            $this->userCustomFieldRepository = new UserCustomFieldRepository($this->context);
        }
        return $this->userCustomFieldRepository;
    }

    /**
     * UserAuthorization Repository
     */
    public function UserAuthorizations(): UserAuthorizationRepository
    {
        if ($this->userAuthorizationRepository === null) {
            $this->userAuthorizationRepository = new UserAuthorizationRepository($this->context);
        }
        return $this->userAuthorizationRepository;
    }

    /**
     * Authorization Repository
     */
    public function Authorizations(): AuthorizationRepository
    {
        if ($this->authorizationRepository === null) {
            $this->authorizationRepository = new AuthorizationRepository($this->context);
        }
        return $this->authorizationRepository;
    }

    /**
     * AuthorizationOperationClaim Repository
     */
    public function AuthorizationOperationClaims(): AuthorizationOperationClaimRepository
    {
        if ($this->authorizationOperationClaimRepository === null) {
            $this->authorizationOperationClaimRepository = new AuthorizationOperationClaimRepository($this->context);
        }
        return $this->authorizationOperationClaimRepository;
    }

    /**
     * Save all changes
     */
    public function saveChanges(): int
    {
        return $this->context->saveChanges();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->context->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->context->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->context->rollback();
    }

    /**
     * Discard changes
     */
    public function discardChanges(): void
    {
        // Clear change tracker
        // Implementation depends on DbContext internals
    }
}

