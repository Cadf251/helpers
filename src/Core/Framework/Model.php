<?php

namespace Cadud\Helpers\Core\Framework;

use Cadud\Helpers\Core\Database\DbHelper;
use Cadud\Helpers\Core\Database\SelectQueryBuilder;
use DateTime;
use ReflectionClass;
use ReflectionProperty;

abstract class Model
{
  protected static string $table;
  protected static string $fields = "*";
  protected static string $joins = "";

  protected static bool $timestamps = true;

  protected array $virtual = [];
  protected array $immutable = [];

  public ?int $id = null;
  public DateTime $created_at;
  public ?DateTime $updated_at;

  /**
   * Mapeamento de cast automático de colunas do banco
   * Exemplo no Model filho:
   * protected array $casts = [
   *     'number' => 'int',
   *     'is_active' => 'bool',
   *     'options' => 'json',
   * ];
   */
  protected array $casts = [];

  private function casts(): array
  {
    return $this->casts + ['created_at' => 'datetime', 'updated_at' => 'datetime'];
  }

  /**
   * Insert data directly to Models' attributes, casting if defined
   */
  public function fill(array $data): static
  {
    $reflection = new ReflectionClass($this);

    $casts = $this->casts();

    foreach ($data as $key => $value) {
      if (!$reflection->hasProperty($key)) {
        continue;
      }

      $property = $reflection->getProperty($key);

      // Aplica o Cast se estiver definido
      $type = $casts[$key] ?? null;
      $castedValue = $type ? Caster::cast($value, $type) : $value;

      $property->setValue($this, $castedValue);
    }

    return $this;
  }

  /**
   * Creates and fills the Model
   */
  public static function hydrate(array $row): static
  {
    $instance = new static();
    return $instance->fill($row);
  }

  public static function all(): array
  {
    return static::select(
      static::query()
        ->columns(static::$fields)
    );
  }

  public static function find(?int $id): ?static
  {
    if ($id === null) return null;

    return static::select(
      static::query()
        ->columns(static::$fields)
        ->where("id = :id", ["id" => $id])
        ->limit(1),
      DbHelper::FETCH_ONE
    );
  }

  public static function findBy(string $field, mixed $value): ?static
  {
    $array = DbHelper::findBy(static::$table, $field, $value);

    if (empty($array)) {
      return null;
    }

    return static::hydrate($array);
  }

  public static function select(SelectQueryBuilder $query, int $fetch = DbHelper::FETCH_ALL): static|array|null
  {
    $array = DbHelper::execute($query->toSql(), $query->getParams(), $fetch);

    if (empty($array)) {
      return null;
    }

    if ($fetch === DbHelper::FETCH_ONE) {
      return static::hydrate($array);
    }

    return array_map(fn($row) => static::hydrate($row), $array);
  }


  /**
   * Retorna uma nova instância do SelectQueryBuilder pré-configurada com a tabela do Model
   */
  public static function query(): SelectQueryBuilder
  {
    return SelectQueryBuilder::table(static::$table);
  }

  /**
   * Prepara o array de dados aplicando o uncast antes de enviar ao DbHelper
   */
  private function prepareForDatabase(array $data): array
  {
    $prepared = [];

    $casts = $this->casts();

    foreach ($data as $key => $value) {
      $type = $casts[$key] ?? null;
      $prepared[$key] = Caster::uncast($value, $type);
    }
    return $prepared;
  }

  /**
   * Exporta o objeto para array
   */
  public function toArray(array $except = []): array
  {
    $reflection = new ReflectionClass($this);
    $data = [];

    foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
      $name = $property->getName();

      // Ignora propriedades internas da infraestrutura do Model
      if (in_array($name, $except)) {
        continue;
      }

      if ($property->isInitialized($this)) {
        $value = $property->getValue($this);
        $data[$name] = $value;
      }
    }

    return $data;
  }

  public function save(): void
  {
    if ($this->id === null) {
      $this->create();
    } else {
      $this->update();
    }
  }

  private function create(): void
  {
    if (static::$timestamps) {
      $this->created_at = new DateTime();
      $this->updated_at = null;
    }

    $data = $this->toArray(["id", ...$this->virtual]);

    $this->id = DbHelper::insert(static::$table, $this->prepareForDatabase($data));
  }

  private function update(): void
  {
    if (static::$timestamps) {
      $this->updated_at = new DateTime();
    }

    $data = $this->toArray(["id", ...$this->virtual, ...$this->immutable, "created_at"]);

    DbHelper::update(static::$table, $this->prepareForDatabase($data), $this->id);
  }

  public function delete(): void
  {
    if ($this->id === null) {
      return;
    }

    DbHelper::delete(static::$table, $this->id);

    $this->id = null;
  }
}
