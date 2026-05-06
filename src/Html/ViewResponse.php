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
    $inst->withError = false;
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
    // 1. Remove comentários HTML
    $buffer = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s', '', $buffer);

    // 2. A sua lógica de colapsar espaços, mas com cautela
    $search = [
      '/\>[^\S ]+/s',     // espaços após tags
      '/[^\S ]+\</s',     // espaços antes de tags
      '/(\s)+/s',         // múltiplos espaços vira um
      '/(\r?\n)/'         // remove quebras de linha
    ];

    $replace = ['>', '<', '\\1', ''];

    return preg_replace($search, $replace, $buffer);
  }
}
