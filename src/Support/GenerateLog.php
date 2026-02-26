<?php

namespace Cadud\Helpers\Support;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * Gerar log
 * 
 *  - DEBUG (100): Informação de depuração.
 *  - INFO (200): Eventos interessantes. Por exemplo: um usuário realizou o login ou logs de SQL.
 *  - NOTICE (250): Eventos normais, mas significantes.
 *  - WARNING (300): Ocorrências excepcionais, mas que não são erros. Por exemplo: Uso de APIs descontinuadas, uso inadequado de uma API. Em geral coisas que não estão erradas mas precisam de atenção.
 *  - ERROR (400): Erros de tempo de execução que não requerem ação imediata, mas que devem ser logados e monitorados.
 *  - CRITICAL (500): Condições criticas. Por exemplo: Um componente da aplicação não está disponível, uma exceção não esperada ocorreu.
 *  - ALERT (550): Uma ação imediata deve ser tomada. Exemplo: O sistema caiu, o banco de dados está indisponível , etc. Deve disparar um alerta para o responsável tomar providencia o mais rápido possível.
 *  - EMERGENCY (600): Emergência: O sistema está inutilizável.
 * 
 * @author Cadu <cadu.devmarketing@gmail.com>
 */
class GenerateLog
{
  private static string $local;

  const DEBUG = "debug";
  const INFO = "info";
  const NOTICE = "notice";
  const WARNING = "warning";
  const ERROR = "error";
  const CRITICAL = "critical";
  const ALERT = "alert";
  const EMERGENCY = "emergency";

  public static function config(?string $path = null)
  {
    self::$local = $path ?? $_ENV['LOG_PATH'];

    return self::$local;
  }

  // MÉTODO NOVO: Recebe o objeto de erro diretamente
  public static function log(\Throwable $e, $level = self::ERROR, array $info = [])
  {
    $dadosDoErro = [
      'arquivo'  => $e->getFile(),
      'linha'    => $e->getLine(),
    ];

    // Chama o método antigo internamente para salvar, 
    self::generateLog($level, $e->getMessage(), array_merge($dadosDoErro, $info));
  }

  public static function generateLog(string $level, string $message, ?array $content = []):void
  {
    $log = new Logger("name");

    // Criar o caminho do log
    $logDir = self::$local ?? self::config();

    // Nome do arquivo
    $nameFileLog = date("dmY") . ".log";

    $filePath = "$logDir/$nameFileLog";

    // Verifica se o arquivo existe
    if (!file_exists($filePath)){
    
      // Abre o arquivo
      $fileOpen = fopen($filePath, "w");

      // Fecha o arquivo
      fclose($fileOpen);
    }

    $log->pushHandler(new StreamHandler($filePath), Level::Debug);
    
    // Salvar o log no arquivo
    $log->$level($message, $content);
  }
}