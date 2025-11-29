<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Yakupeyisan\CodeIgniter4\EntityFramework\Migrations\MigrationManager;

/**
 * Migration Command
 * EF Core benzeri migration komutları
 * 
 * Kullanım:
 *   php spark migration add MigrationName
 *   php spark migration update
 *   php spark migration rollback [--steps=1]
 *   php spark migration remove MigrationName
 *   php spark migration list
 */
class Migration extends BaseCommand
{
    protected $group       = 'Migration';
    protected $name        = 'migration';
    protected $description = 'Entity Framework benzeri migration komutları';
    protected $usage       = 'migration [add|update|rollback|remove|list] [options]';
    protected $arguments   = [
        'action' => 'İşlem: add, update, rollback, remove, list'
    ];
    protected $options     = [
        '--name'    => 'Migration adı (add için)',
        '--steps'   => 'Rollback adım sayısı (varsayılan: 1)',
        '--target'  => 'Hedef migration (update için)'
    ];
    
    protected $params = [];

    public function run(array $params)
    {
        // Store params for use in sub-methods
        $this->params = $params;
        
        $action = $params[0] ?? null;
        
        if (empty($action)) {
            CLI::error('İşlem belirtilmedi!');
            CLI::write('Kullanılabilir işlemler: add, update, rollback, list');
            CLI::write('Örnek: php spark migration add MigrationName');
            return;
        }

        $manager = new MigrationManager();

        switch ($action) {
            case 'add':
                $this->addMigration($manager);
                break;
            case 'update':
                $this->updateDatabase($manager);
                break;
            case 'rollback':
                $this->rollbackMigration($manager);
                break;
            case 'remove':
                $this->removeMigration($manager);
                break;
            case 'list':
                $this->listMigrations($manager);
                break;
            default:
                CLI::error("Geçersiz işlem: {$action}");
                CLI::newLine();
                CLI::write("Kullanılabilir işlemler: add, update, rollback, remove, list");
        }
    }

    /**
     * Add migration (Add-Migration)
     */
    private function addMigration(MigrationManager $manager): void
    {
        // Get name from --name option or from params[1] (second argument)
        $name = CLI::getOption('name');
        
        // If not provided via option, try to get from params
        if (empty($name) && isset($this->params[1])) {
            $name = $this->params[1];
        }
        
        if (empty($name)) {
            CLI::error('Migration adı gerekli!');
            CLI::write('Kullanım: php spark migration add MigrationName');
            CLI::write('veya: php spark migration add --name=MigrationName');
            return;
        }

        CLI::write("Migration oluşturuluyor: {$name}", 'yellow');
        
        // Try to generate migration from ApplicationDbContext
        // MigrationManager will automatically call generateMigrationFromContext if no callables provided
        // Pass ApplicationDbContext class name explicitly
        $contextClass = 'App\EntityFramework\ApplicationDbContext';
        $generated = $manager->generateMigrationFromContext($contextClass);
        
        if ($generated && !empty(trim($generated['up'])) && !empty(trim($generated['down']))) {
            CLI::write("ApplicationDbContext'ten otomatik migration oluşturuluyor...", 'cyan');
            
            // Create migration without callables - addMigration will use generated code automatically
            $fileName = $manager->addMigration($name);
        } else {
            CLI::write("Otomatik migration oluşturulamadı, boş template kullanılıyor...", 'yellow');
            
            // Fallback to empty template
            $fileName = $manager->addMigration($name, function($builder) {
                // Migration kodları buraya gelecek
                // Örnek:
                // $builder->createTable('NewTable', function($columns) {
                //     $columns->integer('Id')->primaryKey()->autoIncrement();
                //     $columns->string('Name', 255)->notNull();
                // });
            }, function($builder) {
                // Rollback kodları buraya gelecek
                // Örnek:
                // $builder->dropTable('NewTable');
            });
        }

        CLI::write("Migration oluşturuldu: {$fileName}", 'green');
        CLI::write("Dosya konumu: " . APPPATH . "Database/Migrations/{$fileName}", 'cyan');
        CLI::newLine();
        CLI::write("Migration dosyasını düzenleyip 'php spark migration:update' komutu ile uygulayın.", 'yellow');
    }

    /**
     * Update database (Update-Database)
     */
    private function updateDatabase(MigrationManager $manager): void
    {
        $target = CLI::getOption('target');
        
        CLI::write("Veritabanı güncelleniyor...", 'yellow');
        
        try {
            $manager->updateDatabase($target);
            CLI::write("Veritabanı başarıyla güncellendi!", 'green');
        } catch (\Exception $e) {
            CLI::error("Hata: " . $e->getMessage());
            CLI::newLine();
            CLI::write($e->getTraceAsString(), 'red');
        }
    }

    /**
     * Rollback migration
     */
    private function rollbackMigration(MigrationManager $manager): void
    {
        $steps = (int)(CLI::getOption('steps') ?? 1);
        
        CLI::write("Son {$steps} migration geri alınıyor...", 'yellow');
        
        try {
            $manager->rollbackMigration($steps);
            CLI::write("Migration başarıyla geri alındı!", 'green');
        } catch (\Exception $e) {
            CLI::error("Hata: " . $e->getMessage());
            CLI::newLine();
            CLI::write($e->getTraceAsString(), 'red');
        }
    }

    /**
     * Remove migration (Remove-Migration)
     */
    private function removeMigration(MigrationManager $manager): void
    {
        // Get name from --name option or from params[1] (second argument)
        $name = CLI::getOption('name');
        
        // If not provided via option, try to get from params
        if (empty($name) && isset($this->params[1])) {
            $name = $this->params[1];
        }
        
        if (empty($name)) {
            CLI::error('Migration adı gerekli!');
            CLI::write('Kullanım: php spark migration remove MigrationName');
            CLI::write('veya: php spark migration remove --name=MigrationName');
            return;
        }

        // Check if migration exists
        $allMigrations = $manager->getAllMigrations();
        $migrationToRemove = null;
        
        foreach ($allMigrations as $migration) {
            if ($migration['name'] === $name) {
                $migrationToRemove = $migration;
                break;
            }
        }
        
        if (!$migrationToRemove) {
            CLI::error("Migration bulunamadı: {$name}");
            return;
        }

        // Check if migration is applied
        $appliedMigrations = $manager->getAppliedMigrations();
        $appliedNames = array_column($appliedMigrations, 'name');
        $isApplied = in_array($name, $appliedNames);
        
        if ($isApplied) {
            // Check if this is the last applied migration
            $lastApplied = end($appliedMigrations);
            $isLastMigration = ($lastApplied && $lastApplied['name'] === $name);
            
            if (!$isLastMigration) {
                CLI::error("Bu migration uygulanmış ve son migration değil!");
                CLI::write("Sadece son uygulanmış migration kaldırılabilir.", 'yellow');
                CLI::write("Son uygulanmış migration: {$lastApplied['name']}", 'yellow');
                return;
            }
            
            CLI::write("Uyarı: Bu migration veritabanına uygulanmış (son migration)!", 'yellow');
            CLI::write("Migration'ı kaldırmadan önce rollback yapılacak.", 'yellow');
            
            $confirm = CLI::prompt('Devam etmek istiyor musunuz?', ['y', 'n']);
            if ($confirm !== 'y') {
                CLI::write('İşlem iptal edildi.', 'yellow');
                return;
            }
            
            CLI::write("Migration geri alınıyor...", 'yellow');
            try {
                $manager->rollbackMigration(1);
                CLI::write("Migration başarıyla geri alındı!", 'green');
            } catch (\Exception $e) {
                CLI::error("Rollback hatası: " . $e->getMessage());
                CLI::write("Migration dosyası silinmedi.", 'yellow');
                return;
            }
        }

        // Remove migration file
        CLI::write("Migration dosyası siliniyor: {$migrationToRemove['timestamp']}_{$name}.php", 'yellow');
        
        try {
            $result = $manager->removeMigration($name);
            
            if ($result) {
                CLI::write("Migration başarıyla kaldırıldı!", 'green');
            } else {
                CLI::error("Migration dosyası silinemedi!");
            }
        } catch (\Exception $e) {
            CLI::error("Hata: " . $e->getMessage());
        }
    }

    /**
     * List migrations
     */
    private function listMigrations(MigrationManager $manager): void
    {
        $allMigrations = $manager->getAllMigrations();
        $appliedMigrations = $manager->getAppliedMigrations();
        $appliedNames = array_column($appliedMigrations, 'name');

        CLI::newLine();
        CLI::write("MIGRATIONS", 'yellow');
        CLI::write(str_repeat('-', 80), 'yellow');
        CLI::newLine();

        foreach ($allMigrations as $migration) {
            $status = in_array($migration['name'], $appliedNames) ? '✓' : '○';
            $color = in_array($migration['name'], $appliedNames) ? 'green' : 'white';
            
            CLI::write("{$status} {$migration['timestamp']} - {$migration['name']}", $color);
        }

        CLI::newLine();
    }
}

