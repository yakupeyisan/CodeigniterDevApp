# C# EF Core Benzeri SQL Sorgu Yapısı - Roadmap

## Mevcut Durum
- CodeIgniter query builder JOIN'leri WHERE'den sonra eklememize izin vermiyor
- COUNT query'de JOIN'ler çalışıyor, ama `executeQuery()` içinde JOIN'ler kayboluyor
- Eager loading ile her navigation property için ayrı sorgu atılıyor

## Hedef Yapı (C# EF Core)
```
SELECT [s].[Id], [s].[CompanyId], ..., [s0].[Id], ..., [s2].[Id], ..., [s3].[Id], ...
FROM (
    SELECT [u].[Id], [u].[CompanyId], ..., [c].[Id] AS [Id0], ..., [u0].[Id] AS [Id1], ...
    FROM [Users] AS [u]
    INNER JOIN [Companies] AS [c] ON [u].[CompanyId] = [c].[Id]
    LEFT JOIN [UserCustomFields] AS [u0] ON [u].[Id] = [u0].[UserId]
    WHERE [c].[Name] = N'Firma 1' AND [u0].[CustomField01] = N'xxx'
    ORDER BY (SELECT 1)
    OFFSET @__p_0 ROWS FETCH NEXT @__p_1 ROWS ONLY
) AS [s]
LEFT JOIN (UserDepartments subquery) AS [s0] ON [s].[Id] = [s0].[UserId]
LEFT JOIN (UserAuthorizations subquery) AS [s2] ON [s].[Id] = [s2].[UserId]
LEFT JOIN (UserOperationClaims subquery) AS [s3] ON [s].[Id] = [s3].[UserId]
ORDER BY [s].[Id], [s].[Id0], ...
```

## Implementation Roadmap

### Phase 1: Raw SQL Builder Oluştur
1. **`buildEfCoreStyleQuery()` metodu oluştur**
   - CodeIgniter query builder yerine raw SQL string oluştur
   - SQL Server syntax'ına uygun

### Phase 2: Ana Sorgu (Subquery [s]) Oluştur
1. **SELECT kolonlarını belirle**
   - Ana entity kolonları: `[u].[Id]`, `[u].[CompanyId]`, vb.
   - Reference navigation kolonları: `[c].[Id] AS [Id0]`, `[c].[Name]`, vb.
   - One-to-one kolonları: `[u0].[Id] AS [Id1]`, `[u0].[CustomField01]`, vb.

2. **FROM ve JOIN'leri oluştur**
   - Ana tablo: `FROM [Users] AS [u]`
   - Reference navigation JOIN'leri: `INNER JOIN [Companies] AS [c] ON [u].[CompanyId] = [c].[Id]`
   - One-to-one JOIN'leri: `LEFT JOIN [UserCustomFields] AS [u0] ON [u].[Id] = [u0].[UserId]`

3. **WHERE clause'ları ekle**
   - Navigation property WHERE'leri: `[c].[Name] = N'Firma 1'`
   - Simple property WHERE'leri: `[u].[DeletedAt] IS NULL`

4. **ORDER BY ekle**
   - Default: `ORDER BY (SELECT 1)` (EF Core pattern)

5. **OFFSET/FETCH ekle**
   - `OFFSET @__p_0 ROWS FETCH NEXT @__p_1 ROWS ONLY`
   - Parameterized queries kullan

### Phase 3: Nested Collection Subquery'leri Oluştur
1. **UserDepartments Subquery ([s0])**
   - `SELECT [u1].[Id], [u1].[DepartmentId], [u1].[UserId], [d].[Id] AS [Id0], [d].[Name]`
   - `FROM [UserDepartments] AS [u1]`
   - `INNER JOIN [Departments] AS [d] ON [u1].[DepartmentId] = [d].[Id]`

2. **UserAuthorizations Subquery ([s2])**
   - `SELECT [u2].[Id], [u2].[AuthorizationId], [u2].[UserId], [a].[Id] AS [Id0], [a].[Description], [a].[Name], ...`
   - `FROM [UserAuthorizations] AS [u2]`
   - `INNER JOIN [Authorizations] AS [a] ON [u2].[AuthorizationId] = [a].[Id]`
   - **Nested subquery [s1]**: AuthorizationOperationClaims
     - `LEFT JOIN (SELECT [a0].[Id], ... FROM [AuthorizationOperationClaims] AS [a0] INNER JOIN [OperationClaims] AS [o] ...) AS [s1] ON [a].[Id] = [s1].[AuthorizationId]`

3. **UserOperationClaims Subquery ([s3])**
   - `SELECT [u3].[Id], [u3].[OperationClaimId], [u3].[UserId], [o0].[Id] AS [Id0], [o0].[Description], [o0].[Name]`
   - `FROM [UserOperationClaims] AS [u3]`
   - `INNER JOIN [OperationClaims] AS [o0] ON [u3].[OperationClaimId] = [o0].[Id]`

### Phase 4: Subquery'leri Ana Sorguya Bağla
1. **LEFT JOIN'leri ekle**
   - `LEFT JOIN (UserDepartments subquery) AS [s0] ON [s].[Id] = [s0].[UserId]`
   - `LEFT JOIN (UserAuthorizations subquery) AS [s2] ON [s].[Id] = [s2].[UserId]`
   - `LEFT JOIN (UserOperationClaims subquery) AS [s3] ON [s].[Id] = [s3].[UserId]`

### Phase 5: Final SELECT ve ORDER BY
1. **SELECT kolonlarını belirle**
   - Ana entity: `[s].[Id]`, `[s].[CompanyId]`, vb.
   - Reference navigation: `[s].[Id0]`, `[s].[Name]`, vb.
   - One-to-one: `[s].[Id1]`, `[s].[CustomField01]`, vb.
   - Collection navigation: `[s0].[Id]`, `[s0].[Id0]`, `[s0].[Name]`, vb.

2. **ORDER BY ekle**
   - Tüm kolonları sırala: `ORDER BY [s].[Id], [s].[Id0], [s].[Id1], [s0].[Id], [s0].[Id0], [s2].[Id], ...`

### Phase 6: Sonuçları Parse Et
1. **Flat result set'i hierarchical yapıya dönüştür**
   - Her satırı analiz et
   - Ana entity'yi oluştur
   - Navigation property'leri doldur
   - Collection navigation'ları grupla

2. **Entity mapping**
   - `mapToEntities()` metodunu güncelle
   - Nested collection'ları doğru şekilde map et

## Teknik Detaylar

### Column Aliasing
- Reference navigation: `[c].[Id] AS [Id0]`, `[c].[Name]` (no alias)
- One-to-one: `[u0].[Id] AS [Id1]`, `[u0].[CustomField01]` (no alias)
- Nested subquery: `[s1].[Id] AS [Id1]`, `[s1].[AuthorizationId] AS [AuthorizationId0]`

### JOIN Types
- **INNER JOIN**: Many-to-one relationships (Company, Department, Authorization, OperationClaim)
- **LEFT JOIN**: One-to-one relationships (UserCustomField) ve collection subquery'leri

### Parameter Binding
- `OFFSET @__p_0 ROWS FETCH NEXT @__p_1 ROWS ONLY`
- WHERE clause değerleri: `N'Firma 1'` (SQL Server N prefix)

### Entity Column Detection
- Reflection kullanarak entity property'lerini tespit et
- `Column` attribute'undan column name'i al
- Fallback: camelCase to snake_case conversion

## Implementation Steps

1. ✅ **TODO 1**: Ana sorgu oluştur (subquery [s])
2. ✅ **TODO 2**: Nested collection subquery'leri oluştur
3. ✅ **TODO 3**: Nested subquery oluştur (AuthorizationOperationClaims)
4. ✅ **TODO 4**: Subquery'leri ana sorguya LEFT JOIN ile bağla
5. ✅ **TODO 5**: SELECT kolonlarını belirle
6. ✅ **TODO 6**: ORDER BY ekle
7. ✅ **TODO 7**: Raw SQL ile manuel sorgu oluştur

## Öncelikler

1. **Yüksek Öncelik**: Ana sorgu (subquery [s]) - JOIN'ler ve WHERE clause'ları
2. **Orta Öncelik**: Nested collection subquery'leri
3. **Düşük Öncelik**: Nested subquery (AuthorizationOperationClaims içinde)

