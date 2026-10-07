<?php

namespace Cadud\Helpers\Core\Database;

class SelectQueryBuilder
{
  private string $table;
  private string $columns = '*';
  private array $joins = [];
  private array $where = [];
  private ?string $orderBy = null;
  private ?int $limit = null;
  private ?int $offset = null;
  private array $params = [];

  public function __construct(string $table)
  {
    $this->table = DbHelper::sanitizeIdentifier($table);
  }

  public static function table(string $table): self
  {
    return new self($table);
  }

  public function columns(string $columns = "*"): self
  {
    $array = explode(",", $columns);

    $clean = [];
    
    foreach ($array as $item) {
      $clean[] = DbHelper::sanitizeIdentifier($item);
    }

    $this->columns = implode(", ", $clean);
        
    return $this;
  }

  public function where(string $condition, array $params = []): self
  {
    $this->where[] = $condition;
    $this->params = array_merge($this->params, $params);
    return $this;
  }

  public function join(string $clause): self
  {
    $this->joins[] = $clause;
    return $this;
  }

  public function orderBy(string $column, string $direction = 'ASC'): self
  {
    $this->orderBy = DbHelper::sanitizeIdentifier($column) . ' ' . strtoupper($direction);
    return $this;
  }

  public function limit(int $limit, ?int $offset = null): self
  {
    $this->limit = $limit;
    $this->offset = $offset;
    return $this;
  }

  public function toSql(): string
  {
    $sql = "SELECT {$this->columns} FROM {$this->table}";

    if (!empty($this->joins)) {
      $sql .= ' ' . implode(' ', $this->joins);
    }

    if (!empty($this->where)) {
      $sql .= ' WHERE ' . implode(' AND ', $this->where);
    }

    if ($this->orderBy) {
      $sql .= " ORDER BY {$this->orderBy}";
    }

    if ($this->limit !== null) {
      $sql .= " LIMIT {$this->limit}";
      if ($this->offset !== null) {
        $sql .= " OFFSET {$this->offset}";
      }
    }

    return $sql;
  }

  public function getParams(): array
  {
    return $this->params;
  }
}
