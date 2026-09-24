<?php

namespace App\Infra;

class CSRF
{
  private static string $session_key = 'csrf_token';

  // Generates a cryptographically secure random token if one doesn't exist
  public static function generate()
  {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (empty($_SESSION[self::$session_key])) {
      // Uses bin2hex and random_bytes for high security
      $_SESSION[self::$session_key] = bin2hex(random_bytes(32));
    }
    return $_SESSION[self::$session_key];
  }

  // Validates a submitted token against the one stored in the session
  public static function validate()
  {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';

    if (!isset($_SESSION[self::$session_key]) || empty($token)) {
      return false;
    }

    // hash_equals prevents timing attacks
    $validate = hash_equals($_SESSION[self::$session_key], $token);

    // if ($validate) {
    //   unset($_SESSION[self::$session_key]);
    // }
    
    return $validate;
  }

  // Convenience method to output a hidden input field
  public static function insertField()
  {
    echo '<input type="hidden" name="csrf_token" id="csrf" value="' . self::generate() . '">';
  }
}
