<?php

namespace App\EntityFramework\Migrations;

use CodeIgniter\Database\BaseConnection;

/**
 * MigrationBuilder - Fluent API for building migrations
 * Equivalent to MigrationBuilder in EF Core
 */
class MigrationBuilder
{
    private BaseConnection $connection;
    private array $operations = [];

    public function __construct(BaseConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Create table
     */
    public function createTable(string $name, callable $columns): self
    {
        $this->operations[] = [
            'type' => 'createTable',
            'name' => $name,
            'columns' => $columns
        ];
        return $this;
    }

    /**
     * Drop table
     */
    public function dropTable(string $name): self
    {
        $this->operations[] = [
            'type' => 'dropTable',
            'name' => $name
        ];
        return $this;
    }

    /**
     * Add column
     */
    public function addColumn(string $table, string $name, string $type, array $options = []): self
    {
        $this->operations[] = [
            'type' => 'addColumn',
            'table' => $table,
            'name' => $name,
            'columnType' => $type,
            'options' => $options
        ];
        return $this;
    }

    /**
     * Drop column
     */
    public function dropColumn(string $table, string $name): self
    {
        $this->operations[] = [
            'type' => 'dropColumn',
            'table' => $table,
            'name' => $name
        ];
        return $this;
    }

    /**
     * Create index
     */
    public function createIndex(string $table, string $name, array $columns, bool $isUnique = false): self
    {
        $this->operations[] = [
            'type' => 'createIndex',
            'table' => $table,
            'name' => $name,
            'columns' => $columns,
            'isUnique' => $isUnique
        ];
        return $this;
    }

    /**
     * Drop index
     */
    public function dropIndex(string $table, string $name): self
    {
        $this->operations[] = [
            'type' => 'dropIndex',
            'table' => $table,
            'name' => $name
        ];
        return $this;
    }

    /**
     * Add foreign key
     */
    public function addForeignKey(string $table, string $name, array $columns, string $referencedTable, array $referencedColumns, string $onDelete = 'CASCADE'): self
    {
        $this->operations[] = [
            'type' => 'addForeignKey',
            'table' => $table,
            'name' => $name,
            'columns' => $columns,
            'referencedTable' => $referencedTable,
            'referencedColumns' => $referencedColumns,
            'onDelete' => $onDelete
        ];
        return $this;
    }

    /**
     * Drop foreign key
     */
    public function dropForeignKey(string $table, string $name): self
    {
        $this->operations[] = [
            'type' => 'dropForeignKey',
            'table' => $table,
            'name' => $name
        ];
        return $this;
    }

    /**
     * Execute operations
     */
    public function execute(): void
    {
        foreach ($this->operations as $operation) {
            $this->executeOperation($operation);
        }
    }

    /**
     * Execute single operation
     */
    private function executeOperation(array $operation): void
    {
        switch ($operation['type']) {
            case 'createTable':
                $this->executeCreateTable($operation);
                break;
            case 'dropTable':
                $this->connection->query("DROP TABLE IF EXISTS `{$operation['name']}`");
                break;
            case 'addColumn':
                $this->executeAddColumn($operation);
                break;
            case 'dropColumn':
                $this->connection->query("ALTER TABLE `{$operation['table']}` DROP COLUMN `{$operation['name']}`");
                break;
            case 'createIndex':
                $this->executeCreateIndex($operation);
                break;
            case 'dropIndex':
                $this->connection->query("DROP INDEX `{$operation['name']}` ON `{$operation['table']}`");
                break;
            case 'addForeignKey':
                $this->executeAddForeignKey($operation);
                break;
            case 'dropForeignKey':
                $this->connection->query("ALTER TABLE `{$operation['table']}` DROP FOREIGN KEY `{$operation['name']}`");
                break;
        }
    }

    /**
     * Execute create table
     */
    private function executeCreateTable(array $operation): void
    {
        $builder = new \CodeIgniter\Database\Forge($this->connection);
        $fields = [];
        
        if (is_callable($operation['columns'])) {
            $columnBuilder = new ColumnBuilder();
            $operation['columns']($columnBuilder);
            $fields = $columnBuilder->getFields();
        }
        
        $builder->addField($fields);
        $builder->createTable($operation['name']);
    }

    /**
     * Execute add column
     */
    private function executeAddColumn(array $operation): void
    {
        $builder = new \CodeIgniter\Database\Forge($this->connection);
        $field = [
            $operation['name'] => [
                'type' => $operation['columnType'],
                ...$operation['options']
            ]
        ];
        $builder->addColumn($operation['table'], $field);
    }

    /**
     * Execute create index
     */
    private function executeCreateIndex(array $operation): void
    {
        $builder = new \CodeIgniter\Database\Forge($this->connection);
        $builder->addKey($operation['columns'], $operation['isUnique'], false, $operation['name'], $operation['table']);
    }

    /**
     * Execute add foreign key
     */
    private function executeAddForeignKey(array $operation): void
    {
        $builder = new \CodeIgniter\Database\Forge($this->connection);
        $builder->addForeignKey(
            $operation['columns'],
            $operation['referencedTable'],
            $operation['referencedColumns'],
            $operation['onDelete'],
            $operation['name']
        );
    }
}

