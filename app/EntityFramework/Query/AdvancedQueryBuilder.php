<?php

namespace App\EntityFramework\Query;

use App\EntityFramework\Core\DbContext;
use App\EntityFramework\Core\Entity;
use CodeIgniter\Database\BaseConnection;
use ReflectionClass;
use ReflectionProperty;

/**
 * AdvancedQueryBuilder - Main query builder class
 * Equivalent to IQueryable implementation in EF Core
 * Provides comprehensive LINQ-like query operations
 */
class AdvancedQueryBuilder
{
    private DbContext $context;
    private string $entityType;
    private BaseConnection $connection;
    
    // Query building state
    private array $wheres = [];
    private $select = null; // callable|null
    private array $includes = [];
    private array $orderBys = [];
    private ?int $skipCount = null;
    private ?int $takeCount = null;
    private $groupBy = null; // callable|null
    private array $joins = [];
    private bool $isNoTracking = false;
    private bool $isTracking = true;
    private ?string $rawSql = null;
    private array $rawSqlParameters = [];
    private bool $useRawSql = false;

    public function __construct(DbContext $context, string $entityType, BaseConnection $connection)
    {
        $this->context = $context;
        $this->entityType = $entityType;
        $this->connection = $connection;
    }

    /**
     * Add WHERE clause
     */
    public function where(callable $predicate): self
    {
        $this->wheres[] = $predicate;
        return $this;
    }

    /**
     * Add SELECT projection
     */
    public function select(callable $selector): self
    {
        $this->select = $selector;
        return $this;
    }

    /**
     * Add INCLUDE for eager loading
     */
    public function include(string $navigationProperty): self
    {
        $this->includes[] = ['path' => $navigationProperty, 'level' => 0];
        return $this;
    }

    /**
     * Add THEN INCLUDE for nested navigation properties
     */
    public function thenInclude(string $navigationProperty): self
    {
        if (empty($this->includes)) {
            throw new \RuntimeException('ThenInclude must be called after Include');
        }
        
        $lastInclude = &$this->includes[count($this->includes) - 1];
        $lastInclude['thenIncludes'][] = $navigationProperty;
        return $this;
    }

    /**
     * Add ORDER BY
     */
    public function orderBy(callable $keySelector, string $direction = 'ASC'): self
    {
        $this->orderBys[] = ['selector' => $keySelector, 'direction' => $direction];
        return $this;
    }

    /**
     * Add THEN ORDER BY
     */
    public function thenOrderBy(callable $keySelector, string $direction = 'ASC'): self
    {
        $this->orderBys[] = ['selector' => $keySelector, 'direction' => $direction];
        return $this;
    }

    /**
     * Add SKIP
     */
    public function skip(int $count): self
    {
        $this->skipCount = $count;
        return $this;
    }

    /**
     * Add TAKE
     */
    public function take(int $count): self
    {
        $this->takeCount = $count;
        return $this;
    }

    /**
     * Add GROUP BY
     */
    public function groupBy(callable $keySelector): self
    {
        $this->groupBy = $keySelector;
        return $this;
    }

    /**
     * Add JOIN
     */
    public function join(IQueryable $inner, callable $outerKeySelector, callable $innerKeySelector, callable $resultSelector, string $joinType = 'INNER'): self
    {
        $this->joins[] = [
            'inner' => $inner,
            'outerKeySelector' => $outerKeySelector,
            'innerKeySelector' => $innerKeySelector,
            'resultSelector' => $resultSelector,
            'type' => $joinType
        ];
        return $this;
    }

    /**
     * Set AsNoTracking
     */
    public function asNoTracking(): self
    {
        $this->isNoTracking = true;
        $this->isTracking = false;
        return $this;
    }

    /**
     * Set AsTracking
     */
    public function asTracking(): self
    {
        $this->isNoTracking = false;
        $this->isTracking = true;
        return $this;
    }

    /**
     * Set raw SQL
     */
    public function fromSqlRaw(string $sql, array $parameters = []): self
    {
        $this->useRawSql = true;
        $this->rawSql = $sql;
        $this->rawSqlParameters = $parameters;
        return $this;
    }

    /**
     * Execute and get first result
     */
    public function first(): ?object
    {
        $results = $this->executeQuery();
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Execute and get first result or default
     */
    public function firstOrDefault(): ?object
    {
        return $this->first();
    }

    /**
     * Execute and get single result
     */
    public function single(): object
    {
        $results = $this->executeQuery();
        if (count($results) === 0) {
            throw new \RuntimeException('Sequence contains no elements');
        }
        if (count($results) > 1) {
            throw new \RuntimeException('Sequence contains more than one element');
        }
        return $results[0];
    }

    /**
     * Execute and get single result or default
     */
    public function singleOrDefault(): ?object
    {
        $results = $this->executeQuery();
        if (count($results) === 0) {
            return null;
        }
        if (count($results) > 1) {
            throw new \RuntimeException('Sequence contains more than one element');
        }
        return $results[0];
    }

    /**
     * Execute and get all results
     */
    public function toList(): array
    {
        return $this->executeQuery();
    }

    /**
     * Execute and get count
     */
    public function count(): int
    {
        if ($this->useRawSql) {
            $sql = "SELECT COUNT(*) as count FROM ({$this->rawSql}) as subquery";
            $result = $this->connection->query($sql, $this->rawSqlParameters);
            $row = $result->getRowArray();
            return (int)($row['count'] ?? 0);
        }

        $tableName = $this->context->getTableName($this->entityType);
        $builder = $this->connection->table($tableName);
        
        // Apply where clauses
        foreach ($this->wheres as $where) {
            $this->applyWhere($builder, $where);
        }
        
        return $builder->countAllResults();
    }

    /**
     * Check if any exists
     */
    public function any(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Check if all match predicate
     */
    public function all(callable $predicate): bool
    {
        $results = $this->executeQuery();
        foreach ($results as $item) {
            if (!$predicate($item)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Sum
     */
    public function sum(?callable $selector = null)
    {
        $results = $this->executeQuery();
        if (empty($results)) {
            return 0;
        }
        
        if ($selector === null) {
            // Sum all numeric properties
            $sum = 0;
            foreach ($results as $item) {
                if (is_numeric($item)) {
                    $sum += $item;
                }
            }
            return $sum;
        }
        
        $sum = 0;
        foreach ($results as $item) {
            $value = $selector($item);
            if (is_numeric($value)) {
                $sum += $value;
            }
        }
        return $sum;
    }

    /**
     * Average
     */
    public function average(?callable $selector = null)
    {
        $results = $this->executeQuery();
        if (empty($results)) {
            return 0;
        }
        
        $sum = $this->sum($selector);
        return $sum / count($results);
    }

    /**
     * Min
     */
    public function min(?callable $selector = null)
    {
        $results = $this->executeQuery();
        if (empty($results)) {
            return null;
        }
        
        $values = [];
        foreach ($results as $item) {
            $value = $selector ? $selector($item) : $item;
            $values[] = $value;
        }
        
        return min($values);
    }

    /**
     * Max
     */
    public function max(?callable $selector = null)
    {
        $results = $this->executeQuery();
        if (empty($results)) {
            return null;
        }
        
        $values = [];
        foreach ($results as $item) {
            $value = $selector ? $selector($item) : $item;
            $values[] = $value;
        }
        
        return max($values);
    }

    /**
     * Get SQL string
     */
    public function toSql(): string
    {
        if ($this->useRawSql) {
            return $this->rawSql;
        }

        $tableName = $this->context->getTableName($this->entityType);
        $builder = $this->connection->table($tableName);
        
        // Apply where clauses
        foreach ($this->wheres as $where) {
            $this->applyWhere($builder, $where);
        }
        
        // Apply order by
        foreach ($this->orderBys as $orderBy) {
            // Note: CodeIgniter doesn't support callable orderBy directly
            // This would need custom implementation
        }
        
        // Apply skip/take
        if ($this->skipCount !== null) {
            $builder->offset($this->skipCount);
        }
        if ($this->takeCount !== null) {
            $builder->limit($this->takeCount);
        }
        
        return $builder->getCompiledSelect(false);
    }

    /**
     * Execute query and return results
     */
    private function executeQuery(): array
    {
        if ($this->useRawSql) {
            return $this->executeRawSql();
        }

        $tableName = $this->context->getTableName($this->entityType);
        $builder = $this->connection->table($tableName);
        
        // Apply where clauses that don't involve navigation properties
        // Navigation property filters will be applied after loading
        foreach ($this->wheres as $where) {
            // Try to apply simple where clauses (direct property access)
            // Navigation property filters will be skipped here and applied later
            try {
                $this->applyWhere($builder, $where);
            } catch (\Throwable $e) {
                // Navigation property access - will filter in memory
            }
        }
        
        // Apply order by
        foreach ($this->orderBys as $orderBy) {
            $this->applyOrderBy($builder, $orderBy);
        }
        
        // Apply skip/take
        if ($this->skipCount !== null) {
            $builder->offset($this->skipCount);
        }
        if ($this->takeCount !== null) {
            $builder->limit($this->takeCount);
        }
        log_message('debug', $builder->getCompiledSelect());
        // Execute query
        $query = $builder->get();
        $results = $query->getResultArray();
        
        // Convert to entities
        $entities = $this->mapToEntities($results);
        
        // Apply eager loading (includes) - needed for navigation property filtering
        if (!empty($this->includes)) {
            $this->loadIncludes($entities);
        }
        
        // Apply WHERE clauses that may involve navigation properties
        // Filter in memory after loading navigation properties
        foreach ($this->wheres as $where) {
            $entities = array_filter($entities, $where);
        }
        
        // Re-index array after filtering
        $entities = array_values($entities);
        
        // Apply projection if specified
        if ($this->select !== null) {
            $entities = array_map($this->select, $entities);
        }
        
        // Apply change tracking
        if ($this->isTracking && !$this->isNoTracking) {
            foreach ($entities as $entity) {
                if ($entity instanceof Entity) {
                    $entity->enableTracking();
                    $entity->markAsUnchanged();
                }
            }
        }
        
        return $entities;
    }

    /**
     * Execute raw SQL
     */
    private function executeRawSql(): array
    {
        $query = $this->connection->query($this->rawSql, $this->rawSqlParameters);
        $results = $query->getResultArray();
        return $this->mapToEntities($results);
    }

    /**
     * Map database results to entities
     */
    private function mapToEntities(array $results, ?string $entityType = null): array
    {
        $entities = [];
        $entityType = $entityType ?? $this->entityType;
        $reflection = new ReflectionClass($entityType);
        
        foreach ($results as $row) {
            $entity = $reflection->newInstance();
            
            foreach ($row as $column => $value) {
                // Convert column name to property name (camelCase)
                $propertyName = $this->columnToProperty($column);
                
                if ($reflection->hasProperty($propertyName)) {
                    $property = $reflection->getProperty($propertyName);
                    $property->setAccessible(true);
                    
                    // Type conversion
                    $type = $this->getPropertyType($property);
                    $value = $this->convertValue($value, $type);
                    
                    $property->setValue($entity, $value);
                }
            }
            
            $entities[] = $entity;
        }
        
        return $entities;
    }

    /**
     * Convert column name to property name
     */
    private function columnToProperty(string $column): string
    {
        // Handle snake_case to camelCase
        $parts = explode('_', $column);
        $property = $parts[0];
        for ($i = 1; $i < count($parts); $i++) {
            $property .= ucfirst($parts[$i]);
        }
        return $property;
    }

    /**
     * Get property type
     */
    private function getPropertyType(ReflectionProperty $property): ?string
    {
        $type = $property->getType();
        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }
        return null;
    }

    /**
     * Convert value to appropriate type
     */
    private function convertValue($value, ?string $type)
    {
        if ($value === null) {
            return null;
        }
        
        switch ($type) {
            case 'int':
                return (int)$value;
            case 'float':
            case 'double':
                return (float)$value;
            case 'bool':
                return (bool)$value;
            case 'string':
                return (string)$value;
            case 'array':
                return is_string($value) ? json_decode($value, true) : $value;
            default:
                return $value;
        }
    }

    /**
     * Apply WHERE clause
     * Note: Navigation property filtering requires joins and is handled after data loading
     * For now, we only support direct property filtering in SQL
     */
    private function applyWhere($builder, callable $predicate): void
    {
        // For navigation property filtering, we need to:
        // 1. Parse the predicate to detect navigation property access
        // 2. Add necessary JOINs
        // 3. Apply WHERE conditions on joined tables
        
        // This is a complex operation that requires expression tree parsing
        // For now, we'll filter in memory after loading (less efficient but works)
        // Navigation property filters will be stored and applied after data loading
        
        // Store predicate for post-load filtering if it contains navigation properties
        // For simple property filters, we can try to apply them directly
        $reflection = new ReflectionClass($this->entityType);
        $mockEntity = $reflection->newInstance();
        
        // Try to detect if predicate uses navigation properties by checking if it throws
        // when accessing navigation properties on mock entity
        try {
            $result = $predicate($mockEntity);
            // If no exception, it might be a simple property access
            // But we can't reliably parse it without expression tree parsing
        } catch (\Throwable $e) {
            // Navigation property access will throw on null mock entity
            // Store for post-load filtering
        }
        
        // For now, navigation property filtering is done in memory after loading
        // This is stored in $this->wheres and will be applied in executeQuery
    }

    /**
     * Apply ORDER BY
     */
    private function applyOrderBy($builder, array $orderBy): void
    {
        // Similar to applyWhere, this would need expression parsing
        // For now, placeholder implementation
    }

    /**
     * Load includes (eager loading)
     */
    private function loadIncludes(array $entities): void
    {
        if (empty($entities)) {
            return;
        }

        $entityReflection = new ReflectionClass($this->entityType);

        foreach ($this->includes as $include) {
            $navigationProperty = $include['path'];
            
            if (!$entityReflection->hasProperty($navigationProperty)) {
                continue;
            }

            $navProperty = $entityReflection->getProperty($navigationProperty);
            $navProperty->setAccessible(true);
            
            // Get property type from docblock or type hint
            $docComment = $navProperty->getDocComment();
            $relatedEntityType = null;
            $isCollection = false;

            // Check if it's a collection (array) or single entity
            // Pattern: @var TypeName[] or @var TypeName
            if (preg_match('/@var\s+([A-Za-z_][A-Za-z0-9_\\\\]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*)(\[\])?/', $docComment, $matches)) {
                $relatedEntityType = $matches[1];
                $isCollection = !empty($matches[2]);
                
                // If not fully qualified, try to resolve from current entity namespace
                if ($relatedEntityType && !str_starts_with($relatedEntityType, '\\')) {
                    // Try to resolve from same namespace as current entity
                    $currentNamespace = $entityReflection->getNamespaceName();
                    $fullyQualified = $currentNamespace . '\\' . $relatedEntityType;
                    if (class_exists($fullyQualified)) {
                        $relatedEntityType = $fullyQualified;
                    } elseif (class_exists($relatedEntityType)) {
                        // Already a valid class name
                    } else {
                        // Try common namespaces
                        $commonNamespaces = ['App\\Models', 'App\\EntityFramework\\Core'];
                        foreach ($commonNamespaces as $ns) {
                            $fullyQualified = $ns . '\\' . $relatedEntityType;
                            if (class_exists($fullyQualified)) {
                                $relatedEntityType = $fullyQualified;
                                break;
                            }
                        }
                    }
                }
            }

            // Try to get type from property type hint
            if ($relatedEntityType === null && $navProperty->hasType()) {
                $type = $navProperty->getType();
                if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                    $relatedEntityType = $type->getName();
                    $isCollection = false;
                }
            }

            if ($relatedEntityType === null) {
                log_message('debug', "Could not resolve entity type for navigation property: {$navigationProperty}");
                continue;
            }

            // Get foreign key from attributes or property name
            $foreignKey = $this->getForeignKeyForNavigation($entityReflection, $navigationProperty, $isCollection, $this->entityType);
            
            log_message('debug', "Loading navigation: {$navigationProperty} (type: " . ($isCollection ? 'collection' : 'reference') . ", entity: {$relatedEntityType}, FK: {$foreignKey})");

            if ($isCollection) {
                // Load collection navigation (one-to-many)
                $this->loadCollectionNavigation($entities, $navigationProperty, $foreignKey, $relatedEntityType);
            } else {
                // Load reference navigation (many-to-one or one-to-one)
                $this->loadReferenceNavigation($entities, $navigationProperty, $foreignKey, $relatedEntityType, $this->entityType);
            }

            // Load then includes
            if (isset($include['thenIncludes'])) {
                foreach ($include['thenIncludes'] as $thenInclude) {
                    $this->loadThenInclude($entities, $navigationProperty, $thenInclude);
                }
            }
        }
    }

    /**
     * Get foreign key property name for navigation property
     */
    private function getForeignKeyForNavigation(ReflectionClass $entityReflection, string $navigationProperty, bool $isCollection, ?string $parentEntityType = null): ?string
    {
        if ($isCollection) {
            // For collection navigation, foreign key is in the related entity
            // Convention: ParentEntityName + "Id" (e.g., UserId for User entity)
            if ($parentEntityType !== null) {
                $parentClassName = (new ReflectionClass($parentEntityType))->getShortName();
                return $parentClassName . 'Id';
            }
            // Fallback: use current entity type
            $parentClassName = $entityReflection->getShortName();
            return $parentClassName . 'Id';
        } else {
            // For reference navigation, foreign key can be in current entity (many-to-one) 
            // or in related entity (one-to-one)
            // Convention: NavigationPropertyName + "Id" or check ForeignKey attribute
            $fkPropertyName = $navigationProperty . 'Id';
            
            // Check if FK property exists in current entity (many-to-one)
            if ($entityReflection->hasProperty($fkPropertyName)) {
                return $fkPropertyName;
            }

            // Check ForeignKey attribute on properties in current entity
            foreach ($entityReflection->getProperties() as $property) {
                $attributes = $property->getAttributes(\App\EntityFramework\Attributes\ForeignKey::class);
                if (!empty($attributes)) {
                    $fkAttr = $attributes[0]->newInstance();
                    if ($fkAttr->navigationProperty === $navigationProperty) {
                        return $property->getName();
                    }
                }
            }

            // If not found in current entity, it's likely one-to-one where FK is in related entity
            // Convention: ParentEntityName + "Id" (e.g., UserId for User entity)
            if ($parentEntityType !== null) {
                $parentClassName = (new ReflectionClass($parentEntityType))->getShortName();
                return $parentClassName . 'Id';
            }
            
            // Fallback: use current entity type
            $parentClassName = $entityReflection->getShortName();
            return $parentClassName . 'Id';
        }
    }

    /**
     * Load reference navigation (many-to-one or one-to-one)
     */
    private function loadReferenceNavigation(array $entities, string $navigationProperty, string $foreignKey, string $relatedEntityType, ?string $entityType = null): void
    {
        // Use provided entity type or default to $this->entityType
        $entityType = $entityType ?? $this->entityType;
        $entityReflection = new ReflectionClass($entityType);
        
        // Check if foreign key exists in current entity (many-to-one)
        $fkInCurrentEntity = $entityReflection->hasProperty($foreignKey);
        
        if ($fkInCurrentEntity) {
            // Many-to-one: Foreign key is in current entity (e.g., CompanyId in User)
            $foreignKeyValues = [];
            foreach ($entities as $entity) {
                $reflection = new ReflectionClass($entity);
                $property = $reflection->getProperty($foreignKey);
                $property->setAccessible(true);
                $value = $property->getValue($entity);
                if ($value !== null) {
                    $foreignKeyValues[] = $value;
                }
            }
            
            if (empty($foreignKeyValues)) {
                return;
            }
            
            // Load related entities by their Id
            $relatedTableName = $this->context->getTableName($relatedEntityType);
            $builder = $this->connection->table($relatedTableName);
            $builder->whereIn('Id', array_unique($foreignKeyValues));
            $query = $builder->get();
            $relatedResults = $query->getResultArray();
            
            // Map to entities
            $relatedEntities = $this->mapToEntities($relatedResults, $relatedEntityType);
            
            // Group by Id
            $grouped = [];
            foreach ($relatedEntities as $relatedEntity) {
                $reflection = new ReflectionClass($relatedEntity);
                $idProperty = $reflection->getProperty('Id');
                $idProperty->setAccessible(true);
                $id = $idProperty->getValue($relatedEntity);
                $grouped[$id] = $relatedEntity;
            }
            
            // Assign to navigation properties
            foreach ($entities as $entity) {
                $entityRef = new ReflectionClass($entity);
                $fkProperty = $entityRef->getProperty($foreignKey);
                $fkProperty->setAccessible(true);
                $fkValue = $fkProperty->getValue($entity);
                
                if (isset($grouped[$fkValue])) {
                    $navProperty = $entityRef->getProperty($navigationProperty);
                    $navProperty->setAccessible(true);
                    $navProperty->setValue($entity, $grouped[$fkValue]);
                }
            }
        } else {
            // Foreign key is NOT in current entity, so it must be in related entity
            // This is one-to-one where FK is in related entity (e.g., UserCustomField.UserId -> User.Id)
            // Get all current entity IDs
            $entityIds = [];
            foreach ($entities as $entity) {
                $entityRef = new ReflectionClass($entity);
                $idProperty = $entityRef->getProperty('Id');
                $idProperty->setAccessible(true);
                $id = $idProperty->getValue($entity);
                if ($id !== null) {
                    $entityIds[] = $id;
                }
            }
            
            if (empty($entityIds)) {
                return;
            }
            
            // Load related entities where foreign key (in related entity) matches current entity IDs
            $relatedTableName = $this->context->getTableName($relatedEntityType);
            $relatedReflection = new ReflectionClass($relatedEntityType);
            
            // Check if foreign key exists in related entity
            if (!$relatedReflection->hasProperty($foreignKey)) {
                log_message('error', "Foreign key '{$foreignKey}' not found in related entity '{$relatedEntityType}'");
                return;
            }
            
            // Get column name from Column attribute or use property name
            $fkColumnName = $this->getColumnNameFromProperty($relatedReflection, $foreignKey);
            
            $builder = $this->connection->table($relatedTableName);
            $builder->whereIn($fkColumnName, array_unique($entityIds));
            $query = $builder->get();
            $relatedResults = $query->getResultArray();
            
            log_message('debug', "Loading one-to-one navigation (FK in related): {$navigationProperty} from {$relatedTableName} where {$fkColumnName} IN (" . implode(',', $entityIds) . ") - Found " . count($relatedResults) . " results");
            
            // Map to entities
            $relatedEntities = $this->mapToEntities($relatedResults, $relatedEntityType);
            
            // Group by foreign key value
            $grouped = [];
            foreach ($relatedEntities as $relatedEntity) {
                $reflection = new ReflectionClass($relatedEntity);
                $fkProperty = $reflection->getProperty($foreignKey);
                $fkProperty->setAccessible(true);
                $fkValue = $fkProperty->getValue($relatedEntity);
                
                if ($fkValue !== null) {
                    $grouped[$fkValue] = $relatedEntity;
                }
            }
            
            // Assign to navigation properties
            foreach ($entities as $entity) {
                $entityRef = new ReflectionClass($entity);
                $idProperty = $entityRef->getProperty('Id');
                $idProperty->setAccessible(true);
                $id = $idProperty->getValue($entity);
                
                if (isset($grouped[$id])) {
                    $navProperty = $entityRef->getProperty($navigationProperty);
                    $navProperty->setAccessible(true);
                    $navProperty->setValue($entity, $grouped[$id]);
                } else {
                    // Set to null if no related entity found
                    $navProperty = $entityRef->getProperty($navigationProperty);
                    $navProperty->setAccessible(true);
                    $navProperty->setValue($entity, null);
                }
            }
        }
    }

    /**
     * Load collection navigation (one-to-many)
     */
    private function loadCollectionNavigation(array $entities, string $navigationProperty, string $foreignKey, string $relatedEntityType): void
    {
        // Get all entity IDs
        $entityIds = [];

        foreach ($entities as $entity) {
            $entityRef = new ReflectionClass($entity);
            $idProperty = $entityRef->getProperty('Id');
            $idProperty->setAccessible(true);
            $id = $idProperty->getValue($entity);
            if ($id !== null) {
                $entityIds[] = $id;
            }
        }

        if (empty($entityIds)) {
            return;
        }

        // Load related entities where foreign key matches entity IDs
        $relatedTableName = $this->context->getTableName($relatedEntityType);
        $relatedReflection = new ReflectionClass($relatedEntityType);
        $fkColumnName = $this->getColumnNameFromProperty($relatedReflection, $foreignKey);
        
        $builder = $this->connection->table($relatedTableName);
        $builder->whereIn($fkColumnName, array_unique($entityIds));
        $query = $builder->get();
        $relatedResults = $query->getResultArray();
        
        // Debug log
        log_message('debug', "Loading collection navigation: {$navigationProperty} from {$relatedTableName} where {$foreignKey} IN (" . implode(',', $entityIds) . ") - Found " . count($relatedResults) . " results");

        // Map to entities
        $relatedEntities = $this->mapToEntities($relatedResults, $relatedEntityType);

        // Group by foreign key
        $grouped = [];
        foreach ($relatedEntities as $relatedEntity) {
            $reflection = new ReflectionClass($relatedEntity);
            $fkProperty = $reflection->getProperty($foreignKey);
            $fkProperty->setAccessible(true);
            $fkValue = $fkProperty->getValue($relatedEntity);
            
            if ($fkValue !== null) {
                if (!isset($grouped[$fkValue])) {
                    $grouped[$fkValue] = [];
                }
                $grouped[$fkValue][] = $relatedEntity;
            }
        }

        // Assign to navigation properties
        foreach ($entities as $entity) {
            $entityRef = new ReflectionClass($entity);
            $idProperty = $entityRef->getProperty('Id');
            $idProperty->setAccessible(true);
            $id = $idProperty->getValue($entity);
            
            if (isset($grouped[$id])) {
                $navProperty = $entityRef->getProperty($navigationProperty);
                $navProperty->setAccessible(true);
                $navProperty->setValue($entity, $grouped[$id]);
            } else {
                // Initialize empty array if no related entities
                $navProperty = $entityRef->getProperty($navigationProperty);
                $navProperty->setAccessible(true);
                $navProperty->setValue($entity, []);
            }
        }
    }

    /**
     * Load then include (nested navigation properties)
     */
    private function loadThenInclude(array $entities, string $parentNavigation, string $navigationProperty): void
    {
        // Get all parent navigation property values
        $parentEntities = [];
        $entityReflection = new ReflectionClass($this->entityType);
        $parentNavProperty = $entityReflection->getProperty($parentNavigation);
        $parentNavProperty->setAccessible(true);

        foreach ($entities as $entity) {
            $parentValue = $parentNavProperty->getValue($entity);
            
            if (is_array($parentValue)) {
                // Collection navigation
                foreach ($parentValue as $parentEntity) {
                    if ($parentEntity !== null) {
                        $parentEntities[] = $parentEntity;
                    }
                }
            } elseif ($parentValue !== null) {
                // Reference navigation
                $parentEntities[] = $parentValue;
            }
        }

        if (empty($parentEntities)) {
            return;
        }

        // Get the type of parent entities
        $parentEntityType = get_class($parentEntities[0]);
        $parentReflection = new ReflectionClass($parentEntityType);

        if (!$parentReflection->hasProperty($navigationProperty)) {
            return;
        }

        $navProperty = $parentReflection->getProperty($navigationProperty);
        $navProperty->setAccessible(true);
        
        // Get docblock to determine if collection or reference
        $docComment = $navProperty->getDocComment();
        $relatedEntityType = null;
        $isCollection = false;

        if (preg_match('/@var\s+([A-Za-z_][A-Za-z0-9_\\\\]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*)(\[\])?/', $docComment, $matches)) {
            $relatedEntityType = $matches[1];
            $isCollection = !empty($matches[2]);
            
            // If not fully qualified, try to resolve from parent entity namespace
            if ($relatedEntityType && !str_starts_with($relatedEntityType, '\\')) {
                $parentNamespace = $parentReflection->getNamespaceName();
                $fullyQualified = $parentNamespace . '\\' . $relatedEntityType;
                if (class_exists($fullyQualified)) {
                    $relatedEntityType = $fullyQualified;
                } elseif (class_exists($relatedEntityType)) {
                    // Already a valid class name
                } else {
                    // Try common namespaces
                    $commonNamespaces = ['App\\Models', 'App\\EntityFramework\\Core'];
                    foreach ($commonNamespaces as $ns) {
                        $fullyQualified = $ns . '\\' . $relatedEntityType;
                        if (class_exists($fullyQualified)) {
                            $relatedEntityType = $fullyQualified;
                            break;
                        }
                    }
                }
            }
        }

        if ($relatedEntityType === null) {
            return;
        }

        // Get foreign key
        $foreignKey = $this->getForeignKeyForNavigation($parentReflection, $navigationProperty, $isCollection, $parentEntityType);

        if ($isCollection) {
            $this->loadCollectionNavigation($parentEntities, $navigationProperty, $foreignKey, $relatedEntityType);
        } else {
            $this->loadReferenceNavigation($parentEntities, $navigationProperty, $foreignKey, $relatedEntityType, $parentEntityType);
        }
    }

    /**
     * Batch insert entities
     */
    public function batchInsert(array $entities): int
    {
        if (empty($entities)) {
            return 0;
        }
        
        $tableName = $this->context->getTableName($this->entityType);
        $data = [];
        
        foreach ($entities as $entity) {
            $row = $this->entityToArray($entity);
            $data[] = $row;
        }
        
        return $this->connection->table($tableName)->insertBatch($data) ? count($data) : 0;
    }

    /**
     * Batch update entities
     */
    public function batchUpdate(array $entities): int
    {
        if (empty($entities)) {
            return 0;
        }
        
        $tableName = $this->context->getTableName($this->entityType);
        $updated = 0;
        
        foreach ($entities as $entity) {
            $row = $this->entityToArray($entity);
            $reflection = new ReflectionClass($entity);
            $idProperty = $reflection->getProperty('Id');
            $idProperty->setAccessible(true);
            $id = $idProperty->getValue($entity);
            
            if ($this->connection->table($tableName)->where('Id', $id)->update($row)) {
                $updated++;
            }
        }
        
        return $updated;
    }

    /**
     * Convert entity to array
     */
    private function entityToArray(object $entity): array
    {
        $reflection = new ReflectionClass($entity);
        $data = [];
        
        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }
            
            $property->setAccessible(true);
            $value = $property->getValue($entity);
            
            // Skip navigation properties
            if (is_object($value) && !($value instanceof \DateTime)) {
                continue;
            }
            
            $columnName = $this->propertyToColumn($property->getName());
            $data[$columnName] = $value;
        }
        
        return $data;
    }

    /**
     * Convert property name to column name
     */
    private function propertyToColumn(string $property): string
    {
        // Convert camelCase to snake_case
        return strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($property)));
    }

    /**
     * Get column name from Column attribute or property name
     */
    private function getColumnNameFromProperty(ReflectionClass $entityReflection, string $propertyName): string
    {
        if ($entityReflection->hasProperty($propertyName)) {
            $property = $entityReflection->getProperty($propertyName);
            $attributes = $property->getAttributes(\App\EntityFramework\Attributes\Column::class);
            
            if (!empty($attributes)) {
                $columnAttr = $attributes[0]->newInstance();
                if ($columnAttr->name !== null) {
                    return $columnAttr->name;
                }
            }
        }
        
        // Fallback: use property name as-is (SQL Server typically uses PascalCase)
        return $propertyName;
    }
}

