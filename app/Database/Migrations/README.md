# Migration Kullanım Kılavuzu

Bu dokümantasyon, Entity Framework Core benzeri migration sisteminin nasıl kullanılacağını açıklar.

## Komutlar

### 1. Migration Oluşturma (Add-Migration)

Yeni bir migration oluşturmak için:

```bash
php spark migration:add MigrationName
```

veya

```bash
php spark migration add --name=CreateUsersTable
```

Bu komut, `app/Database/Migrations/` klasöründe yeni bir migration dosyası oluşturur.

### 2. Migration Uygulama (Update-Database)

Migration'ları veritabanına uygulamak için:

```bash
php spark migration:update
```

Belirli bir migration'a kadar uygulamak için:

```bash
php spark migration:update --target=20241128120000_CreateUsersTable
```

### 3. Migration Geri Alma (Rollback)

Son migration'ı geri almak için:

```bash
php spark migration:rollback
```

Birden fazla migration geri almak için:

```bash
php spark migration:rollback --steps=3
```

### 4. Migration Listesi

Tüm migration'ları ve durumlarını görmek için:

```bash
php spark migration:list
```

## Migration Dosyası Örneği

Oluşturulan migration dosyası şu şekilde görünür:

```php
<?php

namespace App\Database\Migrations;

use App\EntityFramework\Migrations\Migration;
use App\EntityFramework\Migrations\MigrationBuilder;
use App\EntityFramework\Migrations\ColumnBuilder;

class Migration_20241128120000_CreateUsersTable extends Migration
{
    public function up(): void
    {
        $builder = new MigrationBuilder($this->connection);
        
        $builder->createTable('Users', function(ColumnBuilder $columns) {
            $columns->integer('Id')->primaryKey()->autoIncrement();
            $columns->string('FirstName', 100)->notNull();
            $columns->string('LastName', 100)->notNull();
            $columns->integer('CompanyId')->notNull();
            $columns->datetime('CreatedAt')->nullable();
            $columns->datetime('UpdatedAt')->nullable();
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
        
        $builder->execute();
    }

    public function down(): void
    {
        $builder = new MigrationBuilder($this->connection);
        
        $builder->dropForeignKey('Users', 'FK_Users_Companies');
        $builder->dropIndex('Users', 'IX_Users_CompanyId');
        $builder->dropTable('Users');
        
        $builder->execute();
    }
}
```

## MigrationBuilder Metodları

### Tablo İşlemleri

```php
// Tablo oluştur
$builder->createTable('TableName', function(ColumnBuilder $columns) {
    // Kolon tanımlamaları
});

// Tablo sil
$builder->dropTable('TableName');
```

### Kolon İşlemleri

```php
// Kolon ekle
$builder->addColumn('TableName', 'ColumnName', 'VARCHAR(255)', [
    'null' => false,
    'default' => ''
]);

// Kolon sil
$builder->dropColumn('TableName', 'ColumnName');
```

### Index İşlemleri

```php
// Index oluştur
$builder->createIndex('TableName', 'IX_TableName_Column', ['ColumnName'], false);

// Unique index oluştur
$builder->createIndex('TableName', 'IX_TableName_Column', ['ColumnName'], true);

// Index sil
$builder->dropIndex('TableName', 'IX_TableName_Column');
```

### Foreign Key İşlemleri

```php
// Foreign key ekle
$builder->addForeignKey(
    'TableName',
    'FK_TableName_ReferencedTable',
    ['ForeignKeyColumn'],
    'ReferencedTable',
    ['Id'],
    'CASCADE' // veya 'SET NULL', 'RESTRICT', 'NO ACTION'
);

// Foreign key sil
$builder->dropForeignKey('TableName', 'FK_TableName_ReferencedTable');
```

## ColumnBuilder Metodları

Kolon tanımlamaları için kullanılır:

```php
$columns->integer('Id')->primaryKey()->autoIncrement();
$columns->string('Name', 255)->notNull();
$columns->text('Description')->nullable();
$columns->decimal('Price', 10, 2)->default(0);
$columns->boolean('IsActive')->default(true);
$columns->datetime('CreatedAt')->nullable();
$columns->timestamp('UpdatedAt')->nullable();
```

## Örnek Senaryolar

### Senaryo 1: Yeni Tablo Oluşturma

```php
public function up(): void
{
    $builder = new MigrationBuilder($this->connection);
    
    $builder->createTable('Products', function(ColumnBuilder $columns) {
        $columns->integer('Id')->primaryKey()->autoIncrement();
        $columns->string('Name', 255)->notNull();
        $columns->decimal('Price', 10, 2)->notNull();
        $columns->text('Description')->nullable();
        $columns->boolean('IsActive')->default(true);
        $columns->datetime('CreatedAt')->nullable();
    });
    
    $builder->execute();
}
```

### Senaryo 2: Kolon Ekleme

```php
public function up(): void
{
    $builder = new MigrationBuilder($this->connection);
    
    $builder->addColumn('Users', 'Email', 'VARCHAR(255)', [
        'null' => false,
        'default' => ''
    ]);
    
    $builder->createIndex('Users', 'IX_Users_Email', ['Email'], true);
    
    $builder->execute();
}
```

### Senaryo 3: Foreign Key Ekleme

```php
public function up(): void
{
    $builder = new MigrationBuilder($this->connection);
    
    $builder->addColumn('Orders', 'UserId', 'INT', ['null' => false]);
    
    $builder->addForeignKey(
        'Orders',
        'FK_Orders_Users',
        ['UserId'],
        'Users',
        ['Id'],
        'CASCADE'
    );
    
    $builder->execute();
}
```

## Notlar

1. Her migration dosyası benzersiz bir timestamp içerir.
2. Migration'lar sırayla uygulanır (timestamp'e göre).
3. `up()` metodu migration'ı uygular, `down()` metodu geri alır.
4. Migration'lar `migrations` tablosunda takip edilir.
5. Hata durumunda migration otomatik olarak geri alınmaz, manuel müdahale gerekebilir.

## Sorun Giderme

### Migration uygulanmıyor

- Migration dosyasının doğru namespace'de olduğundan emin olun.
- `migrations` tablosunun var olduğunu kontrol edin.
- Migration dosyasında syntax hatası olup olmadığını kontrol edin.

### Foreign key hatası

- Referans edilen tablonun var olduğundan emin olun.
- Foreign key kolonunun doğru tipte olduğunu kontrol edin.
- Veritabanı engine'inin (InnoDB) foreign key'leri desteklediğinden emin olun.

