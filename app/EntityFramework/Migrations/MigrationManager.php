<?php

namespace App\EntityFramework\Migrations;

use CodeIgniter\Database\BaseConnection;

/**
 * MigrationManager - Manages migrations
 * Equivalent to migration commands in EF Core (Add-Migration, Update-Database, etc.)
 */
class MigrationManager
{
    private BaseConnection $connection;
    private string $migrationsPath;

    public function __construct(?BaseConnection $connection = null, ?string $migrationsPath = null)
    {
        if ($connection === null) {
            // CodeIgniter 4 way to get database connection
            $this->connection = \Config\Database::connect();
        } else {
            $this->connection = $connection;
        }
        $this->migrationsPath = $migrationsPath ?? APPPATH . 'Database/Migrations/';
    }

    /**
     * Add migration (equivalent to Add-Migration)
     */
    public function addMigration(string $migrationName, callable $up, callable $down): string
    {
        $timestamp = date('YmdHis');
        $className = 'Migration_' . $timestamp . '_' . $migrationName;
        $fileName = $timestamp . '_' . $migrationName . '.php';
        $filePath = $this->migrationsPath . $fileName;

        $content = $this->generateMigrationContent($className, $up, $down);
        file_put_contents($filePath, $content);

        return $fileName;
    }

    /**
     * Update database (equivalent to Update-Database)
     */
    public function updateDatabase(?string $targetMigration = null): void
    {
        $migrations = $this->getPendingMigrations();
        
        if ($targetMigration !== null) {
            $migrations = array_filter($migrations, fn($m) => $m['name'] <= $targetMigration);
        }

        foreach ($migrations as $migration) {
            $this->runMigration($migration, 'up');
        }
    }

    /**
     * Remove migration (equivalent to Remove-Migration)
     */
    public function removeMigration(string $migrationName): bool
    {
        $files = glob($this->migrationsPath . '*_' . $migrationName . '.php');
        if (empty($files)) {
            return false;
        }

        foreach ($files as $file) {
            unlink($file);
        }

        return true;
    }

    /**
     * Rollback migration
     */
    public function rollbackMigration(int $steps = 1): void
    {
        $migrations = $this->getAppliedMigrations();
        $migrations = array_slice($migrations, -$steps);

        foreach ($migrations as $migration) {
            $this->runMigration($migration, 'down');
        }
    }

    /**
     * Get pending migrations
     */
    private function getPendingMigrations(): array
    {
        $allMigrations = $this->getAllMigrations();
        $appliedMigrations = $this->getAppliedMigrations();
        $appliedNames = array_column($appliedMigrations, 'name');

        return array_filter($allMigrations, fn($m) => !in_array($m['name'], $appliedNames));
    }

    /**
     * Get all migrations
     */
    private function getAllMigrations(): array
    {
        $files = glob($this->migrationsPath . '*.php');
        $migrations = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $parts = explode('_', $name, 2);
            if (count($parts) === 2) {
                $migrations[] = [
                    'timestamp' => $parts[0],
                    'name' => $parts[1],
                    'file' => $file
                ];
            }
        }

        usort($migrations, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
        return $migrations;
    }

    /**
     * Get applied migrations
     */
    private function getAppliedMigrations(): array
    {
        // Check migrations table
        if (!$this->connection->tableExists('migrations')) {
            $this->createMigrationsTable();
            return [];
        }

        $query = $this->connection->query("SELECT * FROM migrations ORDER BY timestamp DESC");
        $results = $query->getResultArray();

        return array_map(fn($r) => ['timestamp' => $r['timestamp'], 'name' => $r['name']], $results);
    }

    /**
     * Run migration
     */
    private function runMigration(array $migration, string $direction): void
    {
        require_once $migration['file'];
        $className = 'App\\Database\\Migrations\\Migration_' . $migration['timestamp'] . '_' . $migration['name'];
        
        if (class_exists($className)) {
            $migrationInstance = new $className($this->connection);
            if ($direction === 'up') {
                $migrationInstance->up();
                $this->recordMigration($migration);
            } else {
                $migrationInstance->down();
                $this->removeMigrationRecord($migration);
            }
        }
    }

    /**
     * Record migration
     */
    private function recordMigration(array $migration): void
    {
        if (!$this->connection->tableExists('migrations')) {
            $this->createMigrationsTable();
        }

        $this->connection->table('migrations')->insert([
            'timestamp' => $migration['timestamp'],
            'name' => $migration['name'],
            'applied_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Remove migration record
     */
    private function removeMigrationRecord(array $migration): void
    {
        $this->connection->table('migrations')
            ->where('timestamp', $migration['timestamp'])
            ->where('name', $migration['name'])
            ->delete();
    }

    /**
     * Create migrations table
     */
    private function createMigrationsTable(): void
    {
        $forge = new \CodeIgniter\Database\Forge($this->connection);
        $forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'timestamp' => ['type' => 'VARCHAR(14)'],
            'name' => ['type' => 'VARCHAR(255)'],
            'applied_at' => ['type' => 'DATETIME']
        ]);
        $forge->addKey('id', true);
        $forge->createTable('migrations');
    }

    /**
     * Generate migration content
     */
    private function generateMigrationContent(string $className, callable $up, callable $down): string
    {
        return <<<PHP
<?php

namespace App\Database\Migrations;

use App\EntityFramework\Migrations\Migration;
use App\EntityFramework\Migrations\MigrationBuilder;

class {$className} extends Migration
{
    public function up(): void
    {
        \$builder = new MigrationBuilder(\$this->connection);
        // Migration code here
    }

    public function down(): void
    {
        \$builder = new MigrationBuilder(\$this->connection);
        // Rollback code here
    }
}
PHP;
    }
}

