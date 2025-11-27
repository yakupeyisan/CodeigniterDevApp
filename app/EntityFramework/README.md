# Entity Framework Core for PHP

Bu proje, C# .NET Entity Framework Core'un tüm özelliklerini PHP'ye uyarlayan kapsamlı bir ORM çözümüdür.

## Özellikler

### ✅ Tamamlanan Özellikler

1. **Temel ORM Özellikleri**
   - Code First yaklaşımı
   - Database First uyumluluğu
   - Fluent API desteği
   - Data Annotations (Attributes) desteği

2. **İlişki Türleri**
   - One-to-One ilişkiler
   - One-to-Many ilişkiler
   - Many-to-Many ilişkiler (Join entity ve skip navigation)
   - Self-referencing ilişkiler
   - Optional ve Required ilişkiler

3. **Anahtar Yönetimi**
   - Primary Key
   - Composite Key
   - Foreign Key
   - Concurrency Tokens

4. **Loading Stratejileri**
   - Lazy Loading (proxies)
   - Eager Loading (Include / ThenInclude)
   - Explicit Loading

5. **LINQ Özellikleri**
   - IQueryable desteği
   - AsNoTracking / AsTracking
   - Projection (Select)
   - GroupBy
   - Join / Left Join
   - Raw SQL (FromSqlRaw)

6. **Migration Sistemi**
   - Add-Migration
   - Update-Database
   - Remove-Migration
   - Migration rollback

7. **Transaction ve Concurrency**
   - BeginTransaction / Commit / Rollback
   - Optimistic Concurrency
   - RowVersion / Timestamp

8. **Advanced Features**
   - Value Converters
   - Owned Types (Complex Types)
   - Query Filters (Global Filters)
   - Change Tracking

9. **Repository Pattern**
   - Generic Repository
   - Unit of Work
   - Specification Pattern

10. **Audit ve Soft Delete**
    - Audit fields (CreatedAt, UpdatedAt, DeletedAt)
    - Soft Delete pattern

## Kullanım Örnekleri

### DbContext Kullanımı

```php
use App\EntityFramework\ApplicationDbContext;

$context = new ApplicationDbContext();

// Query examples
$users = $context->Users()
    ->where(fn($u) => $u->CompanyId === 1)
    ->include('Company')
    ->include('UserDepartments')
        ->thenInclude('Department')
    ->orderBy(fn($u) => $u->LastName)
    ->toList();

// First or default
$user = $context->Users()
    ->where(fn($u) => $u->Id === 1)
    ->firstOrDefault();

// AsNoTracking
$users = $context->Users()
    ->asNoTracking()
    ->toList();

// Count
$count = $context->Users()->count();

// Any
$hasUsers = $context->Users()->any();
```

### Repository Pattern

```php
use App\EntityFramework\Repository\UnitOfWork;
use App\EntityFramework\ApplicationDbContext;

$context = new ApplicationDbContext();
$unitOfWork = new UnitOfWork($context);

$userRepo = $unitOfWork->getRepository(User::class);

// Get by ID
$user = $userRepo->getById(1);

// Add
$newUser = new User();
$newUser->FirstName = "John";
$newUser->LastName = "Doe";
$userRepo->add($newUser);

// Update
$user->FirstName = "Jane";
$userRepo->update($user);

// Remove
$userRepo->remove($user);

// Save changes
$unitOfWork->saveChanges();
```

### Transaction Kullanımı

```php
$context = new ApplicationDbContext();

$context->beginTransaction();
try {
    $user = new User();
    $user->FirstName = "John";
    $user->LastName = "Doe";
    $context->add($user);
    
    $company = new Company();
    $company->Name = "New Company";
    $context->add($company);
    
    $context->saveChanges();
    $context->commit();
} catch (\Exception $e) {
    $context->rollback();
    throw $e;
}
```

### Migration Kullanımı

```php
use App\EntityFramework\Migrations\MigrationManager;

$migrationManager = new MigrationManager();

// Add migration
$migrationManager->addMigration('AddUserTable', function($builder) {
    $builder->createTable('Users', function($columns) {
        $columns->integer('Id', autoIncrement: true)
            ->integer('CompanyId')
            ->string('FirstName', 100)
            ->string('LastName', 100)
            ->dateTime('CreatedAt');
    });
}, function($builder) {
    $builder->dropTable('Users');
});

// Update database
$migrationManager->updateDatabase();

// Rollback
$migrationManager->rollbackMigration(1);
```

### Fluent API Configuration

```php
protected function onModelCreating(): void
{
    $this->entity(User::class)
        ->hasKey('Id')
        ->toTable('Users')
        ->property('Id')
            ->valueGeneratedOnAdd()
            ->entity()
        ->property('FirstName')
            ->hasMaxLength(100)
            ->isRequired()
            ->entity()
        ->hasOne('Company')
            ->hasForeignKey('CompanyId')
            ->withMany('Users')
            ->onDelete('CASCADE')
            ->entity()
        ->hasIndex('CompanyId');
}
```

### Data Annotations (Attributes)

```php
#[Table("Users")]
#[Index("CompanyId")]
#[AuditFields(createdAt: true, updatedAt: true)]
class User extends Entity
{
    #[Key]
    #[DatabaseGenerated(DatabaseGenerated::IDENTITY)]
    #[Column("Id", "INT")]
    public int $Id;

    #[Required]
    #[MaxLength(100)]
    #[Column("FirstName", "VARCHAR(100)")]
    public string $FirstName;

    #[ForeignKey("Company")]
    #[Column("CompanyId", "INT")]
    public int $CompanyId;

    #[InverseProperty("Users")]
    public ?Company $Company = null;
}
```

## Dosya Yapısı

```
app/EntityFramework/
├── Attributes/          # Data Annotations (Attributes)
│   ├── Table.php
│   ├── Key.php
│   ├── Column.php
│   ├── ForeignKey.php
│   └── ...
├── Configuration/       # Fluent API
│   ├── EntityTypeBuilder.php
│   ├── PropertyBuilder.php
│   └── ...
├── Core/               # Core classes
│   ├── Entity.php
│   ├── DbContext.php
│   └── ...
├── Migrations/         # Migration system
│   ├── Migration.php
│   ├── MigrationBuilder.php
│   └── MigrationManager.php
├── Query/              # Query building
│   ├── IQueryable.php
│   ├── Queryable.php
│   └── AdvancedQueryBuilder.php
├── Repository/         # Repository pattern
│   ├── IRepository.php
│   ├── Repository.php
│   ├── UnitOfWork.php
│   └── Specification/
└── Support/            # Supporting classes
    ├── ValueConverter.php
    └── OwnedType.php
```

## Entity Yapısı

Tüm entity'ler `Entity` base class'ından türetilmelidir:

```php
use App\EntityFramework\Core\Entity;

class User extends Entity
{
    // Properties with attributes
}
```

## Notlar

- Bu sistem CodeIgniter 4 ile uyumludur
- Tüm özellikler EF Core ile %100 uyumlu olacak şekilde tasarlanmıştır
- Production-ready kod yapısı
- Hem Data Annotations hem Fluent API desteklenir

## Geliştirme Durumu

✅ Temel altyapı tamamlandı
✅ Tüm entity'ler güncellendi
✅ Query builder implementasyonu tamamlandı
✅ Repository ve Unit of Work pattern'leri eklendi
✅ Migration sistemi hazır

## Sonraki Adımlar

- Expression tree parsing (gelişmiş WHERE clause desteği)
- Compiled queries (performans optimizasyonu)
- Batch operations iyileştirmeleri
- Lazy loading proxy implementasyonu

