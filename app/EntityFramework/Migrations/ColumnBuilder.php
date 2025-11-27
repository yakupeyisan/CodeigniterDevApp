<?php

namespace App\EntityFramework\Migrations;

/**
 * ColumnBuilder - Fluent API for building columns in migrations
 */
class ColumnBuilder
{
    private array $fields = [];

    /**
     * Add integer column
     */
    public function integer(string $name, bool $autoIncrement = false, bool $nullable = false): self
    {
        $this->fields[$name] = [
            'type' => 'INT',
            'auto_increment' => $autoIncrement,
            'null' => $nullable
        ];
        return $this;
    }

    /**
     * Add string column
     */
    public function string(string $name, ?int $length = null, bool $nullable = false): self
    {
        $this->fields[$name] = [
            'type' => $length ? "VARCHAR({$length})" : 'VARCHAR(255)',
            'null' => $nullable
        ];
        return $this;
    }

    /**
     * Add text column
     */
    public function text(string $name, bool $nullable = false): self
    {
        $this->fields[$name] = [
            'type' => 'TEXT',
            'null' => $nullable
        ];
        return $this;
    }

    /**
     * Add decimal column
     */
    public function decimal(string $name, int $precision = 18, int $scale = 2, bool $nullable = false): self
    {
        $this->fields[$name] = [
            'type' => "DECIMAL({$precision},{$scale})",
            'null' => $nullable
        ];
        return $this;
    }

    /**
     * Add boolean column
     */
    public function boolean(string $name, bool $nullable = false): self
    {
        $this->fields[$name] = [
            'type' => 'TINYINT(1)',
            'null' => $nullable
        ];
        return $this;
    }

    /**
     * Add date column
     */
    public function date(string $name, bool $nullable = false): self
    {
        $this->fields[$name] = [
            'type' => 'DATE',
            'null' => $nullable
        ];
        return $this;
    }

    /**
     * Add datetime column
     */
    public function dateTime(string $name, bool $nullable = false): self
    {
        $this->fields[$name] = [
            'type' => 'DATETIME',
            'null' => $nullable
        ];
        return $this;
    }

    /**
     * Add timestamp column
     */
    public function timestamp(string $name, bool $nullable = false): self
    {
        $this->fields[$name] = [
            'type' => 'TIMESTAMP',
            'null' => $nullable
        ];
        return $this;
    }

    /**
     * Add JSON column
     */
    public function json(string $name, bool $nullable = false): self
    {
        $this->fields[$name] = [
            'type' => 'JSON',
            'null' => $nullable
        ];
        return $this;
    }

    /**
     * Set default value
     */
    public function defaultValue($value): self
    {
        $lastKey = array_key_last($this->fields);
        if ($lastKey !== null) {
            $this->fields[$lastKey]['default'] = $value;
        }
        return $this;
    }

    /**
     * Get fields
     */
    public function getFields(): array
    {
        return $this->fields;
    }
}

