<?php

namespace Cadud\Helpers\Html;

class LoadLayout
{
  /**
   * Carrega uma view e retorna com erro caso algo tenha acontecido
   * 
   * @param string $path O caminho físico absoluto
   * @param array $view As informações que devem ser passadas para a view
   * 
   * @return ViewResponse 
   */
  public static function loadView(string $path, array $view = []): ViewResponse
  {
    if (!file_exists($path)) return ViewResponse::error();

    ob_start();
    require $path;
    $content = ob_get_clean();

    if ($content === false) {
      return ViewResponse::error();
    }

    return new ViewResponse($content);
  }
}