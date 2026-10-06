<?php

namespace Cadud\Helpers\Core\Framework;

use Cadud\Helpers\Core\Database\DbHelper;
use Cadud\Helpers\Core\Database\SelectQueryBuilder;
use ReflectionClass;
use ReflectionProperty;

abstract class Model
{
  protected static string $table;
  protected static string $fields = "*";
  protected static string $joins = "";

  /**
   * Mapeamento de cast automático de colunas do banco
   * Exemplo no Model filho:
   * protected array $casts = [
   *     'id' => 'int',
   *     'is_active' => 'bool',
   *     'options' => 'json',
   *     'created_at' => 'datetime'
   * ];
   */
  protected array $casts = [];

  public function fill(array $data): static
  {
    $reflection = new ReflectionClass($this);

    foreach ($data as $key => $value) {
      if (!$reflection->hasProperty($key)) {
        continue;
      }

      $property = $reflection->getProperty($key);

      // Aplica o Cast se estiver definido
      $type = $this->casts[$key] ?? null;
      $castedValue = $type ? Caster::cast($value, $type) : $value;

      $property->setValue($this, $castedValue);
    }

    return $this;
  }

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
      DbHelper::FETCH_ONE);
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

    if($fetch === DbHelper::FETCH_ONE) {
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

  protected function create(array $data): int
  {
    return DbHelper::insert(static::$table, $this->prepareForDatabase($data));
  }

  protected function update(int $id, array $data): void
  {
    DbHelper::update(static::$table, $this->prepareForDatabase($data), $id);
  }

  /**
   * Prepara o array de dados aplicando o uncast antes de enviar ao DbHelper
   */
  private function prepareForDatabase(array $data): array
  {
    $prepared = [];
    foreach ($data as $key => $value) {
      $type = $this->casts[$key] ?? null;
      $prepared[$key] = Caster::uncast($value, $type);
    }
    return $prepared;
  }

  /**
   * Exporta o objeto para array (útil para rotas/JSON)
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
        $type = $this->casts[$name] ?? null;
        $data[$name] = Caster::uncast($value, $type);
      }
    }

    return $data;
  }

  abstract public function save();

  abstract public function delete(): void;
}
