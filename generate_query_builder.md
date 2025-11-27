AdvencedQueryBuilder.php dosyasının sağlaması gereken özellikler.
Mevcut projede kullanılan entity yapısını, C# .NET Entity Framework Core ile %100 uyumlu ve eksiksiz hale getirmelisin. Aşağıdaki tüm özellikler tam olarak desteklenmeli ve üretilen kodlar buna göre güncellenmelidir:

1. Temel ORM Özellikleri

- Code First

- Database First uyumluluğu

- Fluent API desteği

- Data Annotations desteği

- Scaffold-DbContext uyumluluğu

2. İlişki Türleri

- One-to-One

- One-to-Many

- Many-to-Many (With join entity and skip navigation)

- Self-referencing relations

- Optional ve Required ilişkiler

3. Anahtar Yönetimi

- Primary Key

- Composite Key

- Alternate Key

- Foreign Key

- Shadow Properties

- Concurrency Tokens

4. Lazy / Eager / Explicit Loading

- Lazy Loading Proxies

- Eager Loading (Include / ThenInclude)

- Explicit Loading

5. LINQ Özellikleri

- IQueryable desteği

- AsNoTracking

- AsTracking

- Projection (Select shaping)

- GroupBy

- Join / Left Join

- Raw SQL (FromSqlRaw / ExecuteSqlRaw)

6. Migration Sistemi

- Add-Migration

- Update-Database

- Remove-Migration

- Migration rollback

- Seed data desteği

7. Transaction ve Concurrency

- TransactionScope

- BeginTransaction

- Optimistic Concurrency

- RowVersion / Timestamp

8. Advanced EF Core Features

- Value Converters

- Owned Types (Complex Types)

- Table Splitting

- Entity Splitting

- Temporal Tables

- Query Filters (Global Filters)

- Shadow State properties

9. Performans ve Optimizasyon

- Compiled Queries

- Batch operations

- Tracking behavior optimization

- Index ve Unique Index desteği

10. Validation ve Lifecycle

- Change Tracking

- SaveChanges interceptors

- Soft Delete pattern

- Audit fields (CreatedAt, UpdatedAt, DeletedAt, CreatedBy vs.)

- Domain Events desteği

11. Çoklu Veritabanı Desteği

- SQL Server

- PostgreSQL

- MySQL

- SQLite

- InMemory provider

12. Identity ve Güvenlik

- ASP.NET Core Identity uyumluluğu

- Claims

- Roles

- User ilişkileri

13. JSON ve Özel Tipler

- JSON kolon desteği

- Backing fields

- Enum mapping

- Decimal precision configuration

14. Repository & Unit of Work Pattern

- Generic Repository

- Unit of Work

- Specification Pattern uyumu

ÇIKTI KURALLARI:

- Yazdığın tüm entity sınıfları EF Core ile birebir uyumlu olmalı.

- Hem Data Annotations hem Fluent API yapılandırmasını birlikte sağlamalısın.

- Hiçbir EF Core özelliği eksik bırakılmamalı.

- Kodlar production-ready olmalı.

- Var olan entity yapısını bozmadan genişletilmelidir.

Şimdi mevcut projedeki entity yapısını bu kurallara göre güncelle.

