<?php

namespace Cadud\Helpers\Core\Framework;

use Cadud\Helpers\Core\Database\DbConn;
use Cadud\Helpers\Support\Logger;
use Exception;

abstract class Model
{
  protected static string $table;
  protected static string $fields = "*";
  protected  static string $joins = "";

  public static function all(): array
  {
    return self::selectMany(
      "SELECT " . static::$fields
        .  "FROM " . static::$table
        .  static::$joins,
      [],
    );
  }

  public static function find(?int $id): ?static
  {
    if ($id === null) return null;

    return self::selectOne(
      "SELECT *
        FROM " . static::$table . "
        WHERE id = :id",
      [
        "id" => $id
      ]
    );
  }

  public static function findBy(string $field, mixed $value): ?static
  {
    try {
      $array = DbConn::getInstance()->findBy(static::$table, $field, $value);
    } catch (Exception $e) {
      Logger::error($e);
    }

    if (!isset($array) || !$array || empty($array)) {
      return null;
    }

    return static::hydrate($array);
  }

  public static function selectOne(string $query, array $params)
  {
    try {
      $array = DbConn::getInstance()->execute($query, $params, DbConn::FETCH_ONE);
    } catch (Exception $e) {
      Logger::error($e);
    }

    if (!isset($array) || !$array || empty($array)) {
      return null;
    }

    return static::hydrate($array);
  }

  public static function selectMany(string $query, array $params)
  {
    try {
      $array = DbConn::getInstance()->execute($query, $params, DbConn::FETCH_ALL);
    } catch (Exception $e) {
      Logger::error($e);
    }

    if (!isset($array) || !$array || empty($array)) {
      return null;
    }

    $result = [];

    foreach ($array as $row) {
      $result[] = static::hydrate($row);
    }

    return $result;
  }

  protected function create(array $data)
  {
    $inst = DbConn::getInstance();
    $inst->insert(static::$table, $data);
    return $inst->getLastInsertId();
  }

  protected function update(int $id, array $data)
  {
    DbConn::getInstance()->update(static::$table, $data, $id);
  }

  public function jsonOrNullGetter(array $data): ?string
  {
    if (empty($data)) return null;

    return json_encode($data, JSON_UNESCAPED_UNICODE);
  }

  public abstract function save();

  protected abstract static function hydrate(array $row);

  public abstract function delete(int $id): void;
}
