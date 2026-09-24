<?php

namespace Cadud\Helpers\Support;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonoLogger;
use Throwable;

/**
 * Gerenciador de Logs da Aplicação
 */
class Logger
{
  private static ?string $path = null;
  private static ?MonoLogger $logger = null;

  public static function config(?string $path = null): string
  {
    self::$path = $path ?? $_ENV['LOG_PATH'] ?? __DIR__ . '../../../storage/logs';
    return self::$path;
  }

  public static function observe(): void
  {
    // 1. Captura Exceções não tratadas
    set_exception_handler(function (Throwable $e) {
      self::error($e);

      http_response_code(500);
      exit();
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
      }
    });
  }

  private static function getLogger(): MonoLogger
  {
    $logPath = self::$path ?? self::config();
    $fileName = date("dmY") . ".log";
    $fullPath = rtrim($logPath, '/') . '/' . $fileName;

    // Instancia o Monolog apenas uma vez por requisição
    if (self::$logger === null) {
      self::$logger = new MonoLogger("app");
      self::$logger->pushHandler(new StreamHandler($fullPath, Level::Debug));
    }

    return self::$logger;
  }

  public static function log(string $level, Throwable|string $message, array $context = []): void
  {
    if ($message instanceof Throwable) {
      $context = array_merge($context, [
        "file" => $message->getFile(),
        "line" => $message->getLine(),
        "trace" => explode("\n", $message->getTraceAsString()) // Trace formatado como array
      ]);
      $message = $message->getMessage();
    }

    $logger = self::getLogger();

    if (method_exists($logger, $level)) {
      $logger->$level($message, $context);
    } else {
      $logger->error($message, $context);
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
