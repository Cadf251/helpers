<?php

namespace App\Infra;

class Auth
{
  private const KEY = "auth_email";

  public static function boot()
  {
    if (!isset($_SESSION[self::KEY])) {
      $_SESSION[self::KEY] = false;
    }
  }

  public static function isLoged()
  {
    self::boot();

    return ($_SESSION[self::KEY] !== false);
  }

  public static function saveSession(string $email)
  {
    $_SESSION[self::KEY] = $email;
  }

  public static function logout()
  {
    $_SESSION[self::KEY] = false;
  }
}