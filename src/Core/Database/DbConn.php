<?php

namespace Cadud\Helpers\Core\Database;

use DateTime;
use Exception;
use PDO;
use PDOException;
use PDOStatement;
use Throwable;

/**
 * Keeps static PDO conection and handles Database operations
 */
class DbConn
{
  private static ?self $instance = null;
  private static ?PDO $conn = null;
  private static array $credentials = [];

  private function __construct() {}

  /**
   * Returns its instance. Needs to be configured
   * 
   * @throws Exception If not configured
   * 
   * @return self
   */
  public static function getInstance(): self
  {
    if (self::$instance === null) {
      throw new Exception("[DBConn]: Database not configured");
    }

    return self::$instance;
  }

  /**
   * Configures host and database conection
   * 
   * @return void
   */
  public static function config(
    string $host,
    string $db_name,
    string $db_user,
    string $db_pass
  ): void {
    self::$credentials = [
      "host" => $host,
      "db_name" => $db_name,
      "db_user" => $db_user,
      "db_pass" => $db_pass
    ];
  }

  /**
   * Gets PDO connection
   * 
   * @throws Exception
   * 
   * @return PDO
   */
  public static function getConn(): PDO
  {
    if (self::$conn === null) {
      try {
        self::connect();
      } catch (Exception $e) {
        throw new Exception($e->getMessage(), $e->getCode(), $e);
      }
    }

    return self::$conn;
  }

  /**
   * @throws Exception Treated exception
   */
  private static function connect(): void
  {
    if (empty((self::$credentials))) {
      throw new Exception("[DBConn]: Database not configured");
    }

    try {
      self::$conn = new PDO(
        "mysql:host=" . self::$credentials["host"] . ";port=3306;charset=utf8mb4;dbname=" . self::$credentials["db_name"],
        self::$credentials["db_user"],
        self::$credentials["db_pass"]
      );
    } catch (PDOException $e) {
      throw new Exception("[DBConn]: Database not connected: " . $e->getMessage(), 0, $e);
    }
  }

  /**
   * Show the credentials that are used on the moment that the function is called (except for the pass, obviously)
   */
  public static function debug():array
  {
    // Made a copy of the array
    $full = self::$credentials;

    // Hide the pass
    unset($full["db_pass"]);

    // Returns it
    return $full;
  }

  // DB HELPERS. MAYBE IT SHOULD BE ANOTHER CLASS

  public const FETCH_ONE = 1;
  public const FETCH_ALL = 2;

  public function execute(
    string $query,
    ?array $params = null,
    int|false $fetch = false
  ) {
    try {
      $st = self::getConn()->prepare($query);
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), 0, $e);
    }

    if ($params) {
      $this->bind($st, $params);
    }

    try {
      $st->execute();
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), 0, $e);
    }

    if ($fetch) {
      if ($fetch === self::FETCH_ONE) {
        return $st->fetch(PDO::FETCH_ASSOC);
      } else {
        return $st->fetchAll(PDO::FETCH_ASSOC);
      }
    }
  }

  public function findBy(string $table, string $field, mixed $value): array
  {
    try {
      $result = $this->execute(
        "SELECT *
        FROM $table
        WHERE `$field` = :$field",
        [
          "$field" => $value
        ],
        self::FETCH_ONE
      );
      return $result ?: [];
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), 0, $e);
    }
  }

  public function insert(string $table, array $params): int
  {
    $placeholders = [];
    $fields = [];

    foreach ($params as $key => $_) {
      $placeholders[] = ":$key";
      $fields[] = "`$key`";
    }

    try {
      $this->execute(
        "INSERT INTO $table (" . implode(", ", $fields) . ")
        VALUES (" . implode(", ", $placeholders) . ")",
        $params,
      );
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), $e->getCode(), $e);
    }

    return $this->getLastInsertId();
  }

  public function update(string $table, array $params, int $id)
  {
    $set = [];

    foreach ($params as $key => $_) {
      $set[] = "`$key` = :$key";
    }

    $params["id"] = $id;

    try {
      $this->execute(
        "UPDATE $table
        SET 
        " . implode(",\n", $set) . "
        WHERE
          id = :id",
        $params
      );
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), $e->getCode(), $e);
    }
  }

  public function getLastInsertId(): string|false
  {
    return (int)self::$conn->lastInsertId();
  }

  private function bind(PDOStatement $st, array $params)
  {
    foreach ($params as $key => $value) {
      if ($value instanceof DateTime) {
        $value = $value->format("Y-m-d H:i:s");
      }

      try {
        $st->bindValue(":$key", $value, self::paramType($value));
      } catch (Exception $e) {
        throw new Exception($e->getMessage(), $e->getCode(), $e);
      }
    }
  }

  private static function paramType(mixed $value): int
  {
    if (is_int($value))
      return PDO::PARAM_INT;
    else if ($value === null)
      return PDO::PARAM_NULL;
    else if (is_bool($value))
      return PDO::PARAM_BOOL;
    else
      return PDO::PARAM_STR;
  }
}
