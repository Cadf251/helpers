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

    // 2. Processamento de espaços em ordem cronológica perfeita
    $search = [
      '/(\s)+/s',       // Espaços multiplos, tabs, \n, vira u espaço único
      '/\>\s+/s',   // ">  " vira ">"
      '/\s+\</s',   // "  <" vira "<"
      '/ \>/s'          // "<div >" vira "<div>"
    ];

    $replace = ['\\1', '>', '<', '>'];

    return preg_replace($search, $replace, $buffer);
  }
}
