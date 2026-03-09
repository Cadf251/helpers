<?php

namespace Cadud\Helpers\Formatters;

class Formatter
{
  public static function name(string $name): string
  {
    // Remove espaços extras e caracteres inválidos
    $name = trim($name);
    $name = preg_replace('/[^a-zA-ZÀ-ÿ\'\s]/u', '', $name); // permite acentos, hífen e apóstrofo
    $name = preg_replace('/\s+/', ' ', $name); // espaços múltiplos -> um só
    $name = strtolower($name);

    // Lista de palavras que devem ficar minúsculas
    $minusculas = ['da', 'de', 'do', 'das', 'dos', 'e'];

    // Formata cada parte do name
    $partes = explode(' ', $name);
    foreach ($partes as &$parte) {
      if (!in_array($parte, $minusculas)) {
        $parte = ucfirst($parte);
      }
    }

    return implode(' ', $partes);
  }

  public static function phoneToInternational(string $phone): string
  {
    // Remove todos os caracteres não numéricos
    $phone = preg_replace('/\D/', '', $phone);

    // Adiciona DDI 55 se não estiver presente
    if (strpos($phone, '55') !== 0) {
      $phone = '55' . $phone;
    }

    // Se não tiver pelo menos 12 dígitos com o DDI, consideramos inválido
    if (strlen($phone) < 12) return "";

    return $phone;
  }

  public static function phoneToLocal(string $phone): string
  {
    // 1. Remove tudo que não for número
    $phone = preg_replace('/\D/', '', $phone);

    // 2. Remove o DDI (55) se ele estiver no início
    if (strpos($phone, '55') === 0) {
      $phone = substr($phone, 2);
    }

    $len = strlen($phone);

    // 3. Verifica se o tamanho faz sentido (DDD + 8 ou 9 dígitos)
    if ($len < 10 || $len > 11) {
      return $phone; // Retorna o original se não for um padrão reconhecido
    }

    // 4. Aplica a máscara baseada no tamanho
    $ddd = substr($phone, 0, 2);
    
    if ($len === 11) {
      // Celular: (XX) 9XXXX-XXXX
      $part1 = substr($phone, 2, 5);
      $part2 = substr($phone, 7);
      return "($ddd) $part1-$part2";
    } else {
      // Fixo: (XX) XXXX-XXXX
      $part1 = substr($phone, 2, 4);
      $part2 = substr($phone, 6);
      return "($ddd) $part1-$part2";
    }
  }
}