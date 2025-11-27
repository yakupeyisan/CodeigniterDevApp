# Repository Pattern - Özel Repository'ler

Bu dizin, her entity için özelleştirilmiş repository'leri içerir. Her repository, Generic Repository'den türer ve entity'ye özel metodlar sağlar.

## Repository Listesi

### 1. UserRepository
User entity için özel repository. Soft delete, ilişkiler ve özel sorgular içerir.

**Özellikler:**
- `getByCompanyId(int $companyId)` - Şirkete göre kullanıcıları getir
- `getByName(string $firstName, string $lastName)` - İsim-soyisim ile arama
- `searchByName(string $searchTerm)` - İsim içinde arama
- `getWithCompany()` - Company ile birlikte getir
- `getWithAllRelations(int $userId)` - Tüm ilişkileriyle birlikte getir
- `getActiveUsers()` - Soft delete edilmemiş kullanıcılar
- `addDepartment(int $userId, int $departmentId)` - Kullanıcıya departman ekle
- `addAuthorization(int $userId, int $authorizationId)` - Kullanıcıya yetki ekle
- `addOperationClaim(int $userId, int $operationClaimId)` - Kullanıcıya operasyon yetkisi ekle
- `softDelete(User $user)` - Soft delete yap
- `restore(int $userId)` - Soft delete edilmiş kullanıcıyı geri getir

### 2. CompanyRepository
Company entity için özel repository.

**Özellikler:**
- `getByName(string $name)` - İsme göre şirket getir
- `searchByName(string $searchTerm)` - İsim içinde arama
- `getWithUsers(int $companyId)` - Kullanıcılarıyla birlikte getir
- `existsByName(string $name)` - Şirket var mı kontrol et
- `getUserCount(int $companyId)` - Şirketteki kullanıcı sayısı
- `getAllOrderedByName()` - İsme göre sıralı şirketler

### 3. DepartmentRepository
Department entity için özel repository.

**Özellikler:**
- `getByName(string $name)` - İsme göre departman getir
- `searchByName(string $searchTerm)` - İsim içinde arama
- `getWithUsers(int $departmentId)` - Kullanıcılarıyla birlikte getir
- `getUserCount(int $departmentId)` - Departmandaki kullanıcı sayısı
- `getAllOrderedByName()` - İsme göre sıralı departmanlar

### 4. OperationClaimRepository
OperationClaim entity için özel repository.

**Özellikler:**
- `getByName(string $name)` - İsme göre operasyon yetkisi getir
- `searchByName(string $searchTerm)` - İsim içinde arama
- `searchByDescription(string $searchTerm)` - Açıklama içinde arama
- `getAllOrderedByName()` - İsme göre sıralı operasyon yetkileri

### 5. UserOperationClaimRepository
UserOperationClaim (Many-to-Many join) için özel repository.

**Özellikler:**
- `getByUserId(int $userId)` - Kullanıcının operasyon yetkileri
- `getByOperationClaimId(int $operationClaimId)` - Operasyon yetkisindeki kullanıcılar
- `userHasOperationClaim(int $userId, int $operationClaimId)` - Kullanıcı yetki var mı?
- `addOperationClaimToUser(int $userId, int $operationClaimId)` - Kullanıcıya yetki ekle
- `removeOperationClaimFromUser(int $userId, int $operationClaimId)` - Kullanıcıdan yetki kaldır
- `removeAllOperationClaimsFromUser(int $userId)` - Kullanıcının tüm yetkilerini kaldır

### 6. UserDepartmentRepository
UserDepartment (Many-to-Many join) için özel repository.

**Özellikler:**
- `getByUserId(int $userId)` - Kullanıcının departmanları
- `getByDepartmentId(int $departmentId)` - Departmandaki kullanıcılar
- `userInDepartment(int $userId, int $departmentId)` - Kullanıcı departmanda mı?
- `addUserToDepartment(int $userId, int $departmentId)` - Kullanıcıyı departmana ekle
- `removeUserFromDepartment(int $userId, int $departmentId)` - Kullanıcıyı departmandan çıkar
- `removeUserFromAllDepartments(int $userId)` - Kullanıcıyı tüm departmanlardan çıkar

### 7. UserCustomFieldRepository
UserCustomField (One-to-One) için özel repository.

**Özellikler:**
- `getByUserId(int $userId)` - Kullanıcının özel alanları
- `createOrUpdate(UserCustomField $userCustomField)` - Oluştur veya güncelle
- `updateCustomField(int $userId, int $fieldNumber, string $value)` - Belirli alanı güncelle
- `hasCustomFields(int $userId)` - Özel alanlar var mı?

### 8. UserAuthorizationRepository
UserAuthorization (Many-to-Many join) için özel repository.

**Özellikler:**
- `getByUserId(int $userId)` - Kullanıcının yetkileri
- `getByAuthorizationId(int $authorizationId)` - Yetkideki kullanıcılar
- `userHasAuthorization(int $userId, int $authorizationId)` - Kullanıcı yetki var mı?
- `addAuthorizationToUser(int $userId, int $authorizationId)` - Kullanıcıya yetki ekle
- `removeAuthorizationFromUser(int $userId, int $authorizationId)` - Kullanıcıdan yetki kaldır

### 9. AuthorizationRepository
Authorization entity için özel repository.

**Özellikler:**
- `getByName(string $name)` - İsme göre yetki getir
- `getWithOperationClaims(int $authorizationId)` - Operasyon yetkileriyle birlikte
- `getWithUsers(int $authorizationId)` - Kullanıcılarıyla birlikte
- `getWithAllRelations(int $authorizationId)` - Tüm ilişkileriyle birlikte
- `getUserCount(int $authorizationId)` - Yetkideki kullanıcı sayısı

### 10. AuthorizationOperationClaimRepository
AuthorizationOperationClaim (Many-to-Many join) için özel repository.

**Özellikler:**
- `getByAuthorizationId(int $authorizationId)` - Yetkinin operasyon yetkileri
- `addOperationClaimToAuthorization(int $authorizationId, int $operationClaimId)` - Yetkiye operasyon ekle
- `removeOperationClaimFromAuthorization(int $authorizationId, int $operationClaimId)` - Yetkiden operasyon kaldır

## Kullanım Örnekleri

### ApplicationUnitOfWork ile Kullanım

```php
use App\EntityFramework\ApplicationDbContext;
use App\Repositories\ApplicationUnitOfWork;

// DbContext oluştur
$context = new ApplicationDbContext();
$unitOfWork = new ApplicationUnitOfWork($context);

// User Repository
$userRepo = $unitOfWork->Users();
$user = $userRepo->getById(1);
$users = $userRepo->getByCompanyId(1)->toList();
$userRepo->softDelete($user);

// Company Repository
$companyRepo = $unitOfWork->Companies();
$company = $companyRepo->getByName("Acme Corp");
$companies = $companyRepo->searchByName("Acme");

// User Operation Claim Repository
$userOpRepo = $unitOfWork->UserOperationClaims();
$userOpRepo->addOperationClaimToUser(1, 5);
$hasClaim = $userOpRepo->userHasOperationClaim(1, 5);

// Transaction ile kullanım
$unitOfWork->beginTransaction();
try {
    $userRepo->add($newUser);
    $userOpRepo->addOperationClaimToUser($newUser->Id, 1);
    $unitOfWork->saveChanges();
    $unitOfWork->commit();
} catch (\Exception $e) {
    $unitOfWork->rollback();
}
```

### Repository'leri Doğrudan Kullanım

```php
use App\EntityFramework\ApplicationDbContext;
use App\Repositories\UserRepository;

$context = new ApplicationDbContext();
$userRepo = new UserRepository($context);

// Özel metodlar
$activeUsers = $userRepo->getActiveUsers()->toList();
$userWithAllRelations = $userRepo->getWithAllRelations(1);

// Generic metodlar
$user = $userRepo->getById(1);
$userRepo->add($newUser);
$userRepo->update($user);
$userRepo->remove($user);
$userRepo->exists(1);
```

## Best Practices

1. **ApplicationUnitOfWork kullanın**: Tüm repository'ler tek bir UnitOfWork üzerinden yönetilir
2. **Transaction kullanın**: İlişkili işlemlerde mutlaka transaction kullanın
3. **SaveChanges**: Değişiklikleri kaydetmek için `saveChanges()` metodunu çağırın
4. **Özel metodlar**: Her repository'nin entity'ye özel metodlarını kullanın
5. **Generic metodlar**: Temel CRUD işlemleri için generic metodları kullanın

