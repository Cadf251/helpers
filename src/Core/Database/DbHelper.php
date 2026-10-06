<?php

namespace Cadud\Helpers\Core\Database;

use DateTime;
use Exception;
use PDO;
use PDOException;
use PDOStatement;

class DbHelper
{
  public const FETCH_ONE = 1;
  public const FETCH_ALL = 2;

  /**
   * @throws Exception
   */
  public static function execute(
    string $query,
    ?array $params = null,
    int|false $fetch = false
  ): mixed {
    $pdo = DbConn::getConn();
    $st = $pdo->prepare($query);

    if ($params) {
      self::bind($st, $params);
    }

    $st->execute();

    if ($fetch === self::FETCH_ONE) {
      return $st->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    if ($fetch === self::FETCH_ALL) {
      return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    return true;
  }

  public static function findBy(string $table, string $field, mixed $value): array
  {
    $table = self::sanitizeIdentifier($table);
    $field = self::sanitizeIdentifier($field);

    return self::execute(
      "SELECT * FROM {$table} WHERE `{$field}` = :{$field}",
      [$field => $value],
      self::FETCH_ONE
    );
  }

  public static function insert(string $table, array $params): int
  {
    $table = self::sanitizeIdentifier($table);
    $fields = [];
    $placeholders = [];

    foreach (array_keys($params) as $key) {
      $key = self::sanitizeIdentifier($key);
      $fields[] = "`{$key}`";
      $placeholders[] = ":{$key}";
    }

    self::execute(
      "INSERT INTO {$table} (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")",
      $params
    );

    return self::getLastInsertId();
  }

  public static function update(string $table, array $params, int $id): void
  {
    $table = self::sanitizeIdentifier($table);
    $set = [];

    foreach (array_keys($params) as $key) {
      $sanitizedKey = self::sanitizeIdentifier($key);
      $set[] = "`{$sanitizedKey}` = :{$sanitizedKey}";
    }

    $params["id"] = $id;

    self::execute(
      "UPDATE {$table} SET " . implode(", ", $set) . " WHERE id = :id",
      $params
    );
  }

  public static function getLastInsertId(): int
  {
    return (int) DbConn::getConn()->lastInsertId();
  }

  private static function bind(PDOStatement $st, array $params): void
  {
    foreach ($params as $key => $value) {
      if ($value instanceof DateTime) {
        $value = $value->format("Y-m-d H:i:s");
      }

      $st->bindValue(":{$key}", $value, self::paramType($value));
    }
  }

  private static function paramType(mixed $value): int
  {
    return match (true) {
      is_int($value)  => PDO::PARAM_INT,
      is_bool($value) => PDO::PARAM_BOOL,
      is_null($value) => PDO::PARAM_NULL,
      default         => PDO::PARAM_STR,
    };
  }

  /**
   * Sanitiza nomes de colunas e tabelas para evitar SQL Injection
   */
  private static function sanitizeIdentifier(string $identifier): string
  {
    return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
  }
}
