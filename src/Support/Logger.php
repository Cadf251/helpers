<?php

namespace Cadud\Helpers\Support;

use Cadud\Helpers\Core\Framework\ErrorController;
use Exception;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonoLogger;
use Throwable;

class Logger
{
  private static ?string $path = null;
  private static ?MonoLogger $logger = null;

  public static function config(string $path): string
  {
    self::$path = $path;
    return self::$path;
  }

  public static function observe(): void
  {
    if (ob_get_level() === 0) {
      ob_start();
    }

    // 1. Captura Throwables não tratados
    set_exception_handler(function (Throwable $e) {
      self::error($e);

      $wantsJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');

      if ($wantsJson) {
        http_response_code(500);
        echo json_encode(['error' => 'Internal Server Error']);
        exit();
      }

      // Apenas instancia o ErrorController e ele resolve o resto!
      new ErrorController(500, 'Ocorreu um erro inesperado em nosso servidor.', $e);
    });

    // 2. Captura Erros Fatais (Shutdown)
    register_shutdown_function(function () {
      $error = error_get_last();
      if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        self::critical($error['message'], [
          'file' => $error['file'],
          'line' => $error['line'],
          'type' => $error['type']
        ]);

        $wantsJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');

        if ($wantsJson) {
          http_response_code(500);
          echo json_encode(['error' => 'Internal Server Error']);
          exit();
        }

        // Apenas instancia o ErrorController e ele resolve o resto!
        new ErrorController(500, 'Ocorreu um erro inesperado em nosso servidor.');
      }
    });
  }

  private static function getLogger(): ?MonoLogger
  {
    if (self::$path === null) {
      // Em vez de dar throw, registra no log padrão do PHP (Apache/Nginx/syslog)
      error_log("[Logger Error]: Logger path is not configured.");
      return null;
    }

    if (self::$logger === null) {
      try {
        $fileName = date("dmY") . ".log";
        $fullPath = rtrim(self::$path, '/') . '/' . $fileName;

        self::$logger = new MonoLogger("app");
        self::$logger->pushHandler(new StreamHandler($fullPath, Level::Debug));
      } catch (Throwable $e) {
        // Se falhar a escrita do arquivo/permissão, grava no log de erro do sistema
        error_log("[Logger Failure]: " . $e->getMessage());
        return null;
      }
    }

    return self::$logger;
  }

  public static function log(string $level, Throwable|string $message, array $context = []): void
  {
    if ($message instanceof Throwable) {
      $context = array_merge($context, [
        "file"  => $message->getFile(),
        "line"  => $message->getLine(),
        "trace" => explode("\n", $message->getTraceAsString())
      ]);
      $message = $message->getMessage();
    }

    try {
      $logger = self::getLogger();

      // Se o Monolog estiver disponível, grava nele
      if ($logger !== null) {
        if (method_exists($logger, $level)) {
          $logger->$level($message, $context);
        } else {
          $logger->error($message, $context);
        }
        return;
      }

      // FALLBACK: Se o Monolog falhar ou não estiver configurado, usa o error_log nativo do PHP
      $formattedContext = !empty($context) ? ' ' . json_encode($context) : '';
      error_log(strtoupper($level) . ": " . $message . $formattedContext);
    } catch (Throwable $e) {
      // Última barreira: Se tudo der errado, garante que não interrompa o script
      error_log("CRITICAL: Logger failed entirely: " . $e->getMessage());
    }
  }

  public static function info(Throwable|string $message, array $context = []): void
  {
    self::log('info', $message, $context);
  }

  public static function debug(Throwable|string $message, array $context = []): void
  {
    self::log('debug', $message, $context);
  }

  public static function warning(Throwable|string $message, array $context = []): void
  {
    self::log('warning', $message, $context);
  }

  public static function error(Throwable|string $message, array $context = []): void
  {
    self::log('error', $message, $context);
  }

  public static function critical(Throwable|string $message, array $context = []): void
  {
    self::log('critical', $message, $context);
  }
}
