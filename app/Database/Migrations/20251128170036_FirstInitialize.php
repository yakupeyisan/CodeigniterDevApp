<?php

namespace App\Database\Migrations;

use Yakupeyisan\CodeIgniter4\EntityFramework\Migrations\Migration;
use Yakupeyisan\CodeIgniter4\EntityFramework\Migrations\MigrationBuilder;
use Yakupeyisan\CodeIgniter4\EntityFramework\Migrations\ColumnBuilder;

class Migration_20251128170036_FirstInitialize extends Migration
{
    public function up(): void
    {
        $builder = new MigrationBuilder($this->connection);
        
        // Companies table
        $builder->createTable('Companies', function(ColumnBuilder $columns) {
            $columns->integer('Id')->primaryKey()->autoIncrement()->notNull();
            $columns->string('Name', 255)->notNull();
            $columns->datetime('CreatedAt')->nullable();
            $columns->datetime('UpdatedAt')->nullable();
        });
        $builder->createIndex('Companies', 'IX_Companies_Name', ['Name'], true);

        // Departments table
        $builder->createTable('Departments', function(ColumnBuilder $columns) {
            $columns->integer('Id')->primaryKey()->autoIncrement()->notNull();
            $columns->string('Name', 255)->notNull();
            $columns->datetime('CreatedAt')->nullable();
            $columns->datetime('UpdatedAt')->nullable();
        });
        $builder->createIndex('Departments', 'IX_Departments_Name', ['Name'], true);

        // OperationClaims table
        $builder->createTable('OperationClaims', function(ColumnBuilder $columns) {
            $columns->integer('Id')->primaryKey()->autoIncrement()->notNull();
            $columns->string('Name', 255)->notNull();
            $columns->string('Description', 500)->nullable();
            $columns->datetime('CreatedAt')->nullable();
            $columns->datetime('UpdatedAt')->nullable();
        });
        $builder->createIndex('OperationClaims', 'IX_OperationClaims_Name', ['Name'], true);

        // Authorizations table
        $builder->createTable('Authorizations', function(ColumnBuilder $columns) {
            $columns->integer('Id')->primaryKey()->autoIncrement()->notNull();
            $columns->string('Name', 255)->notNull();
            $columns->string('Description', 500)->nullable();
            $columns->datetime('CreatedAt')->nullable();
            $columns->datetime('UpdatedAt')->nullable();
        });
        $builder->createIndex('Authorizations', 'IX_Authorizations_Name', ['Name'], true);

        // Users table
        $builder->createTable('Users', function(ColumnBuilder $columns) {
            $columns->integer('Id')->primaryKey()->autoIncrement()->notNull();
            $columns->integer('CompanyId')->notNull();
            $columns->string('FirstName', 100)->notNull();
            $columns->string('LastName', 100)->notNull();
            $columns->datetime('CreatedAt')->nullable();
            $columns->datetime('UpdatedAt')->nullable();
            $columns->datetime('DeletedAt')->nullable();
        });
        $builder->createIndex('Users', 'IX_Users_CompanyId', ['CompanyId'], false);
        $builder->addForeignKey(
            'Users',
            'FK_Users_Companies',
            ['CompanyId'],
            'Companies',
            ['Id'],
            'CASCADE'
        );

        // UserOperationClaims table
        $builder->createTable('UserOperationClaims', function(ColumnBuilder $columns) {
            $columns->integer('Id')->primaryKey()->autoIncrement()->notNull();
            $columns->integer('UserId')->notNull();
            $columns->integer('OperationClaimId')->notNull();
        });
        $builder->createIndex('UserOperationClaims', 'IX_UserOperationClaims_UserId_OperationClaimId', ['UserId', 'OperationClaimId'], true);
        $builder->addForeignKey(
            'UserOperationClaims',
            'FK_UserOperationClaims_Users',
            ['UserId'],
            'Users',
            ['Id'],
            'CASCADE'
        );
        $builder->addForeignKey(
            'UserOperationClaims',
            'FK_UserOperationClaims_OperationClaims',
            ['OperationClaimId'],
            'OperationClaims',
            ['Id'],
            'CASCADE'
        );

        // UserDepartments table
        $builder->createTable('UserDepartments', function(ColumnBuilder $columns) {
            $columns->integer('Id')->primaryKey()->autoIncrement()->notNull();
            $columns->integer('UserId')->notNull();
            $columns->integer('DepartmentId')->notNull();
        });
        $builder->createIndex('UserDepartments', 'IX_UserDepartments_UserId_DepartmentId', ['UserId', 'DepartmentId'], true);
        $builder->addForeignKey(
            'UserDepartments',
            'FK_UserDepartments_Users',
            ['UserId'],
            'Users',
            ['Id'],
            'CASCADE'
        );
        $builder->addForeignKey(
            'UserDepartments',
            'FK_UserDepartments_Departments',
            ['DepartmentId'],
            'Departments',
            ['Id'],
            'CASCADE'
        );

        // UserCustomFields table
        $builder->createTable('UserCustomFields', function(ColumnBuilder $columns) {
            $columns->integer('Id')->primaryKey()->autoIncrement()->notNull();
            $columns->integer('UserId')->notNull();
            $columns->string('CustomField01', 255)->nullable();
            $columns->string('CustomField02', 255)->nullable();
            $columns->string('CustomField03', 255)->nullable();
            $columns->string('CustomField04', 255)->nullable();
            $columns->string('CustomField05', 255)->nullable();
        });
        $builder->createIndex('UserCustomFields', 'IX_UserCustomFields_UserId', ['UserId'], true);
        $builder->addForeignKey(
            'UserCustomFields',
            'FK_UserCustomFields_Users',
            ['UserId'],
            'Users',
            ['Id'],
            'CASCADE'
        );

        // UserAuthorizations table
        $builder->createTable('UserAuthorizations', function(ColumnBuilder $columns) {
            $columns->integer('Id')->primaryKey()->autoIncrement()->notNull();
            $columns->integer('UserId')->notNull();
            $columns->integer('AuthorizationId')->notNull();
        });
        $builder->createIndex('UserAuthorizations', 'IX_UserAuthorizations_UserId_AuthorizationId', ['UserId', 'AuthorizationId'], true);
        $builder->addForeignKey(
            'UserAuthorizations',
            'FK_UserAuthorizations_Users',
            ['UserId'],
            'Users',
            ['Id'],
            'CASCADE'
        );
        $builder->addForeignKey(
            'UserAuthorizations',
            'FK_UserAuthorizations_Authorizations',
            ['AuthorizationId'],
            'Authorizations',
            ['Id'],
            'CASCADE'
        );

        // AuthorizationOperationClaims table
        $builder->createTable('AuthorizationOperationClaims', function(ColumnBuilder $columns) {
            $columns->integer('Id')->primaryKey()->autoIncrement()->notNull();
            $columns->integer('AuthorizationId')->notNull();
            $columns->integer('OperationClaimId')->notNull();
        });
        $builder->createIndex('AuthorizationOperationClaims', 'IX_AuthorizationOperationClaims_AuthorizationId_OperationClaimId', ['AuthorizationId', 'OperationClaimId'], true);
        $builder->addForeignKey(
            'AuthorizationOperationClaims',
            'FK_AuthorizationOperationClaims_Authorizations',
            ['AuthorizationId'],
            'Authorizations',
            ['Id'],
            'CASCADE'
        );
        $builder->addForeignKey(
            'AuthorizationOperationClaims',
            'FK_AuthorizationOperationClaims_OperationClaims',
            ['OperationClaimId'],
            'OperationClaims',
            ['Id'],
            'CASCADE'
        );


        
        $builder->execute();
    }

    public function down(): void
    {
        $builder = new MigrationBuilder($this->connection);
        
        // Drop tables in reverse order (respecting foreign key constraints)
        $builder->dropTable('AuthorizationOperationClaims');
        $builder->dropTable('UserAuthorizations');
        $builder->dropTable('UserCustomFields');
        $builder->dropTable('UserDepartments');
        $builder->dropTable('UserOperationClaims');
        $builder->dropTable('Users');
        $builder->dropTable('Authorizations');
        $builder->dropTable('OperationClaims');
        $builder->dropTable('Departments');
        $builder->dropTable('Companies');

        
        $builder->execute();
    }
}