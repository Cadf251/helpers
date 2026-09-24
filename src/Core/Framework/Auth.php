<?php

namespace Cadud\Helpers\Core\Framework;

class Auth
{
  private const KEY = "auth_user";
  private const FINGERPRINT_KEY = "auth_fingerprint";

  /**
   * Garante a inicialização da sessão com boas práticas de segurança
   */
  public static function boot(): void
  {
    if (session_status() === PHP_SESSION_NONE) {
      // Diretrizes de segurança de cookie de sessão
      ini_set('session.cookie_httponly', 1); // Impede acesso via JavaScript (XSS)
      ini_set('session.cookie_samesite', 'Lax'); // Proteção adicional CSRF
      ini_set('session.use_only_cookies', 1);

      session_start();
    }

    if (!isset($_SESSION[self::KEY])) {
      $_SESSION[self::KEY] = false;
    }
  }

  /**
   * Checa se o usuário está logado e valida a integridade da sessão
   */
  public static function isLoged(): bool
  {
    self::boot();

    if ($_SESSION[self::KEY] === false) {
      return false;
    }

    // Validação contra Session Hijacking (User-Agent)
    $currentFingerprint = self::generateFingerprint();
    if (!isset($_SESSION[self::FINGERPRINT_KEY]) || $_SESSION[self::FINGERPRINT_KEY] !== $currentFingerprint) {
      self::logout();
      return false;
    }

    return true;
  }

  /**
   * Inicia a sessão autenticada com ID renovado
   */
  public static function saveSession(string $email): void
  {
    self::boot();

    // Previne Session Fixation regenerando o ID de sessão
    session_regenerate_id(true);

    $_SESSION[self::KEY] = $email;
    $_SESSION[self::FINGERPRINT_KEY] = self::generateFingerprint();
  }

  /**
   * Retorna o e-mail ou dados do usuário logado
   */
  public static function user(): ?string
  {
    return self::isLoged() ? $_SESSION[self::KEY] : null;
  }

  /**
   * Destrói completamente a sessão e remove o cookie
   */
  public static function logout(): void
  {
    self::boot();

    $_SESSION = [];

    // Invalida o cookie de sessão no navegador
    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
      );
    }

    session_destroy();
  }

  /**
   * Gera uma assinatura do navegador para validação de integridade
   */
  private static function generateFingerprint(): string
  {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    return md5($userAgent);
  }
}
