<?php

namespace Cadud\Helpers\Html;

class ViewResponse
{
  public bool $withError = false;
  private ?string $html = null;

  public function __construct(string $html)
  {
    $this->html = self::minify($html);
  }

  public static function error(): self
  {
    $inst = new self("");
    $inst->withError = true;
    return $inst;
  }

  public function get()
  {
    return $this->html;
  }

  public function render()
  {
    echo $this->html;
  }

  public static function minify(string $buffer): string
  {
    $buffer = preg_replace('/<!--[\s\S]*?-->/', '', $buffer);

    // Processamento seguro de minificação HTML
    $search = [
      '/\s+/s',          // 1. Reduz múltiplos espaços/quebras de linha para um único espaço
      '/>\s+</s',        // 2. Remove espaços SOMENTE se estiverem ENTRE duas tags (> <)
      '/^\s+|\s+$/m'     // 3. Limpa espaços no início e fim das linhas
    ];

    $replace = [
      ' ',
      '><',
      ''
    ];

    return preg_replace($search, $replace, $buffer);
  }
}
