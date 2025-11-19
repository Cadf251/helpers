<?php

namespace Cadud\Helpers\Html;

use Exception;

class LoadLayout
{
  /**
   * Carrega um require  faz o tratamento de erro se necessário e enviar para um fallback em caso de erro 500
   * 
   * @param string $path O caminho absoluto
   * 
   * @GenerateLog
   * 
   * @return void
   */
  public static function loadLayout(string $path, array $view = [])
  {
    if (!file_exists($path)) return false;

    ob_start();
    require $path;
    $content = ob_get_clean();
    echo self::minify($content);
    return true;
  }
  
  /** Minifica o HTML final */
  public static function minify($buffer)
  {
    // remove quebras de linha e espaços duplicados
    $search = [
      '/\>[^\S ]+/s',     // remove espaços após tags >
      '/[^\S ]+\</s',     // remove espaços antes de tags <
      '/(\s)+/s'          // múltiplos espaços → 1 espaço
    ];

    $replace = ['>', '<', '\\1'];

    return preg_replace($search, $replace, $buffer);
  }
}