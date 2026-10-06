<?php

namespace Cadud\Helpers\Core\Database;

use Exception;
use PDO;
use PDOException;

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
   * @unused Probably
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
}
