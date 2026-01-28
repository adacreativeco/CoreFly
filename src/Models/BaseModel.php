<?php

declare(strict_types=1);

namespace CoreFly\Models;

use CoreFly\Utils\Database;
use PDO;
use Exception;

abstract class BaseModel
{
    protected Database $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected bool $timestamps = true;
    protected ?string $tenantId = null;
    
    // Query Builder State
    protected array $queryConditions = [];
    protected array $queryBindings = [];
    protected ?int $queryLimit = null;
    protected ?int $queryOffset = null;
    protected ?string $queryOrderBy = null;
    
    // Common properties
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function __construct(?array $attributes = null)
    {
        $this->db = Database::getInstance();
        $this->tenantId = $this->db->getCurrentTenant();
        
        if ($attributes !== null) {
            $this->fill($attributes);
        }
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable) || empty($this->fillable)) {
                $this->$key = $this->castAttribute($key, $value);
            }
        }
        return $this;
    }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        if (!isset($this->casts[$key])) {
            return $value;
        }

        $castType = $this->casts[$key];

        return match ($castType) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'array', 'json' => (function($v) {
                if (is_string($v)) {
                    $decoded = json_decode($v, true);
                    return is_array($decoded) ? $decoded : [];
                }
                return is_array($v) ? $v : [];
            })($value),
            'datetime' => $value ? (string)$value : null,
            default => $value,
        };
    }

    public function toArray(): array
    {
        $data = [];
        $properties = get_object_vars($this);
        
        foreach ($properties as $key => $value) {
            if (!in_array($key, $this->hidden) && !str_starts_with($key, '_') && !str_starts_with($key, 'query') && $key !== 'db' && $key !== 'table' && $key !== 'primaryKey' && $key !== 'fillable' && $key !== 'hidden' && $key !== 'casts' && $key !== 'timestamps' && $key !== 'tenantId') {
                if (isset($this->casts[$key])) {
                    if ($this->casts[$key] === 'json' || $this->casts[$key] === 'array') {
                        $data[$key] = is_string($value) ? json_decode($value, true) : $value;
                    } else {
                        $data[$key] = $value;
                    }
                } else {
                    $data[$key] = $value;
                }
            }
        }

        return $data;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    // --- Query Builder Methods ---

    public static function query(): static
    {
        return new static();
    }

    public static function __callStatic(string $name, array $arguments)
    {
        $instance = new static();
        
        if ($name === 'where') {
            return $instance->addWhere(...$arguments);
        }
        
        if ($name === 'first') {
            return $instance->first(...$arguments);
        }

        if ($name === 'all') {
            return $instance->get();
        }

        throw new Exception("Static method {$name} not found");
    }

    public function addWhere(string $column, mixed $value = null, string $operator = '='): static
    {
        // Check for raw SQL condition
        if (str_contains($column, ' ')) {
             $this->queryConditions[] = $column; // Assumes raw SQL snippet like "quantity <= min_quantity"
             if (is_array($value)) {
                 $this->queryBindings = array_merge($this->queryBindings, $value);
             }
             return $this;
        }

        $placeholder = ':' . str_replace('.', '_', $column) . count($this->queryBindings);
        $this->queryConditions[] = "{$column} {$operator} {$placeholder}";
        $this->queryBindings[$placeholder] = $value;
        return $this;
    }
    
    // To support chaining like $query->where(...) after initial static call
    public function __call(string $name, array $arguments)
    {
        if ($name === 'where') {
            return $this->addWhere(...$arguments);
        }
        throw new Exception("Method {$name} not found");
    }

    public function setBindings(array $bindings): static
    {
        foreach ($bindings as $key => $value) {
            $this->queryBindings[':' . $key] = $value;
        }
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->queryOrderBy = "{$column} {$direction}";
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->queryLimit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->queryOffset = $offset;
        return $this;
    }

    public function get(): array
    {
        $sql = "SELECT * FROM {$this->table}";
        
        $conditions = $this->queryConditions;
        if ($this->tenantId) {
            $conditions[] = "tenant_id = :_tenant_id";
            $this->queryBindings[':_tenant_id'] = $this->tenantId;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        if ($this->queryOrderBy) {
            $sql .= " ORDER BY {$this->queryOrderBy}";
        }

        if ($this->queryLimit) {
            $sql .= " LIMIT {$this->queryLimit}";
        }

        if ($this->queryOffset) {
            $sql .= " OFFSET {$this->queryOffset}";
        }

        // DEBUG LOG
        error_log("SQL Query: " . $sql . " Params: " . json_encode($this->queryBindings));

        $stmt = $this->db->prepare($sql);
        
        foreach ($this->queryBindings as $key => $value) {
            // Ensure keys start with :
            $paramKey = str_starts_with($key, ':') ? $key : ':' . $key;
            $stmt->bindValue($paramKey, $value);
        }

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $models = [];
        foreach ($results as $data) {
            $model = new static($data);
            $model->setRawAttributes($data);
            $models[] = $model;
        }

        return $models;
    }

    public function count(): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        
        $conditions = $this->queryConditions;
        if ($this->tenantId) {
            $conditions[] = "tenant_id = :_tenant_id";
            $this->queryBindings[':_tenant_id'] = $this->tenantId;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $stmt = $this->db->prepare($sql);
        
        foreach ($this->queryBindings as $key => $value) {
            $paramKey = str_starts_with($key, ':') ? $key : ':' . $key;
            $stmt->bindValue($paramKey, $value);
        }

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function first(): ?static
    {
        $this->limit(1);
        $results = $this->get();
        return !empty($results) ? $results[0] : null;
    }

    public function firstOrFail(): static
    {
        $model = $this->first();
        if (!$model) {
            throw new Exception("Model not found");
        }
        return $model;
    }

    // --- Static Helpers ---

    public static function find(string $id): ?static
    {
        return static::where('id', $id)->first();
    }

    public static function findOrFail(string $id): static
    {
        $model = static::find($id);
        if (!$model) {
            throw new Exception("Model not found with ID: {$id}");
        }
        return $model;
    }

    public static function all(): array
    {
        return static::query()->get();
    }

    // --- Persistence ---

    public function save(): bool
    {
        if ($this->exists()) {
            return $this->update();
        } else {
            return $this->create();
        }
    }

    public function create(): bool
    {
        if (!isset($this->{$this->primaryKey})) {
            $this->{$this->primaryKey} = $this->generateUuid();
        }

        $now = date('Y-m-d H:i:s');

        if ($this->timestamps) {
            if (empty($this->created_at)) {
                $this->created_at = $now;
            }
            if (empty($this->updated_at)) {
                $this->updated_at = $now;
            }
        }

        $data = $this->getAttributesForDatabase();
        
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($columns), '?');

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        
        try {
            $result = $stmt->execute($values);
            return $result;
        } catch (Exception $e) {
            throw new Exception("Failed to create record: " . $e->getMessage());
        }
    }

    public function update(): bool
    {
        if ($this->timestamps) {
            $this->updated_at = date('Y-m-d H:i:s');
        }

        $data = $this->getAttributesForDatabase();
        unset($data[$this->primaryKey]);

        $sets = [];
        $values = [];

        foreach ($data as $column => $value) {
            $sets[] = "{$column} = ?";
            $values[] = $value;
        }

        $values[] = $this->{$this->primaryKey};

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = ?",
            $this->table,
            implode(', ', $sets),
            $this->primaryKey
        );

        $stmt = $this->db->prepare($sql);
        
        try {
            $result = $stmt->execute($values);
            return $result;
        } catch (Exception $e) {
            throw new Exception("Failed to update record: " . $e->getMessage());
        }
    }

    public function delete(): bool
    {
        if (!$this->exists()) {
            throw new Exception("Cannot delete non-existent record");
        }

        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        
        try {
            $result = $stmt->execute([$this->{$this->primaryKey}]);
            return $result;
        } catch (Exception $e) {
            throw new Exception("Failed to delete record: " . $e->getMessage());
        }
    }

    public function exists(): bool
    {
        return isset($this->{$this->primaryKey});
    }

    protected function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    protected function getAttributesForDatabase(): array
    {
        $data = [];
        $properties = get_object_vars($this);

        foreach ($properties as $key => $value) {
            // Skip timestamps if disabled
            if (!$this->timestamps && ($key === 'created_at' || $key === 'updated_at')) {
                continue;
            }

            if (!str_starts_with($key, '_') && !str_starts_with($key, 'query') && $key !== 'db' && $key !== 'table' && $key !== 'primaryKey' && $key !== 'fillable' && $key !== 'hidden' && $key !== 'casts' && $key !== 'timestamps' && $key !== 'tenantId') {
                if (is_array($value) || is_object($value)) {
                    $data[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
                } else {
                    $data[$key] = $value;
                }
            }
        }

        return $data;
    }

    protected function setRawAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $this->castAttribute($key, $value);
            }
        }
    }

    public function setTenantId(string $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    public function getTenantId(): ?string
    {
        return $this->tenantId;
    }

    public function withoutTenant(): static
    {
        $this->tenantId = null;
        return $this;
    }

    public function fresh(): static
    {
        if (!$this->exists()) {
            return $this;
        }
        return static::find($this->{$this->primaryKey});
    }

    public function refresh(): static
    {
        if (!$this->exists()) {
            return $this;
        }
        $fresh = static::find($this->{$this->primaryKey});
        if ($fresh) {
            $this->setRawAttributes($fresh->toArray());
        }
        return $this;
    }

    // --- Global Query Helpers (Root Admin) ---

    public static function allGlobal(): array
    {
        return static::query()->withoutTenant()->get();
    }

    public static function findGlobal(string $id): ?static
    {
        return static::query()->withoutTenant()->where('id', $id)->first();
    }

    public static function statsGlobal(): int
    {
        return static::query()->withoutTenant()->count();
    }
}
