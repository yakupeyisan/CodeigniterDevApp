# API Controllers

Bu dizin RESTful API controller'larını içerir. Tüm controller'lar `BaseApiController`'dan türer ve Swagger/OpenAPI dokümantasyonu içerir.

## Controller Listesi

### 1. UsersController
User entity için RESTful API endpoints.

**Endpoints:**
- `GET /api/users` - Tüm kullanıcıları listele (pagination, filtering)
- `GET /api/users/{id}` - Kullanıcı detayı
- `GET /api/users/{id}/full` - Tüm ilişkileriyle kullanıcı
- `POST /api/users` - Yeni kullanıcı oluştur
- `PUT /api/users/{id}` - Kullanıcı güncelle
- `DELETE /api/users/{id}` - Kullanıcı sil
- `POST /api/users/{id}/soft-delete` - Soft delete
- `POST /api/users/{id}/restore` - Soft delete'i geri al
- `POST /api/users/{id}/departments` - Kullanıcıya departman ekle
- `POST /api/users/{id}/authorizations` - Kullanıcıya yetki ekle
- `POST /api/users/{id}/operation-claims` - Kullanıcıya operasyon yetkisi ekle

### 2. CompaniesController
Company entity için RESTful API endpoints.

**Endpoints:**
- `GET /api/companies` - Tüm şirketleri listele
- `GET /api/companies/{id}` - Şirket detayı
- `POST /api/companies` - Yeni şirket oluştur
- `PUT /api/companies/{id}` - Şirket güncelle
- `DELETE /api/companies/{id}` - Şirket sil

### 3. DepartmentsController
Department entity için RESTful API endpoints.

**Endpoints:**
- `GET /api/departments` - Tüm departmanları listele
- `GET /api/departments/{id}` - Departman detayı
- `POST /api/departments` - Yeni departman oluştur
- `PUT /api/departments/{id}` - Departman güncelle
- `DELETE /api/departments/{id}` - Departman sil

### 4. OperationClaimsController
OperationClaim entity için RESTful API endpoints.

**Endpoints:**
- `GET /api/operation-claims` - Tüm operasyon yetkilerini listele
- `GET /api/operation-claims/{id}` - Operasyon yetkisi detayı
- `POST /api/operation-claims` - Yeni operasyon yetkisi oluştur
- `PUT /api/operation-claims/{id}` - Operasyon yetkisi güncelle
- `DELETE /api/operation-claims/{id}` - Operasyon yetkisi sil

### 5. AuthorizationsController
Authorization entity için RESTful API endpoints.

**Endpoints:**
- `GET /api/authorizations` - Tüm yetkileri listele
- `GET /api/authorizations/{id}` - Yetki detayı
- `POST /api/authorizations` - Yeni yetki oluştur
- `PUT /api/authorizations/{id}` - Yetki güncelle
- `DELETE /api/authorizations/{id}` - Yetki sil

## BaseApiController

Tüm API controller'ları için base class. Şu özellikleri sağlar:

- **Standardized Responses**: `success()`, `error()`, `validationError()`, `notFound()`
- **Entity Conversion**: `entityToArray()` - Entity'leri array'e dönüştürür
- **Request Parsing**: `createEntityFromRequest()` - Request body'den entity oluşturur
- **Pagination**: `paginatedResponse()` - Pagination desteği

## Response Format

### Başarılı Response
```json
{
    "success": true,
    "message": "Success",
    "data": { ... },
    "timestamp": "2024-01-01 12:00:00"
}
```

### Hata Response
```json
{
    "success": false,
    "message": "Error message",
    "errors": { ... },
    "timestamp": "2024-01-01 12:00:00"
}
```

### Paginated Response
```json
{
    "success": true,
    "data": {
        "items": [ ... ],
        "pagination": {
            "page": 1,
            "per_page": 10,
            "total": 100,
            "total_pages": 10
        }
    }
}
```

## Swagger/OpenAPI

Tüm endpoint'ler Swagger annotations ile dokümante edilmiştir. Swagger UI'ya erişmek için:

- **Swagger UI**: `http://your-domain/swagger`
- **OpenAPI Spec**: `http://your-domain/swagger/spec`

## Kullanım Örnekleri

### GET Request
```bash
curl -X GET http://localhost/api/users?page=1&per_page=10
```

### POST Request
```bash
curl -X POST http://localhost/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "FirstName": "John",
    "LastName": "Doe",
    "CompanyId": 1
  }'
```

### PUT Request
```bash
curl -X PUT http://localhost/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{
    "FirstName": "Jane",
    "LastName": "Doe"
  }'
```

### DELETE Request
```bash
curl -X DELETE http://localhost/api/users/1
```

## Validation

Controller'lar temel validation yapar. Daha gelişmiş validation için CodeIgniter Validation library kullanılabilir.

## Error Handling

Tüm controller'lar try-catch blokları ile hata yönetimi yapar ve uygun HTTP status kodları döndürür:
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

