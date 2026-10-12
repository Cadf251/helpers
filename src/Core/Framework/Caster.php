<?php

namespace Cadud\Helpers\Core\Framework;

use DateTime;

class Caster
{
  /**
   * Converte o valor vindo do Banco de Dados para o tipo PHP
   */
  public static function cast(mixed $value, string $type): mixed
  {
    if ($value === null) {
      return null;
    }

    if (enum_exists($type) && is_subclass_of($type, \BackedEnum::class)) {
      return $type::tryFrom($value);
    }

    return match ($type) {
      'int', 'integer' => (int) $value,
      'float', 'double' => (float) $value,
      'string'         => (string) $value,
      'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
      'json', 'array'  => is_string($value) ? json_decode($value, true) : (array) $value,
      'datetime'       => new DateTime($value),
      default          => $value
    };
  }

  /**
   * Converte o valor do PHP para salvar no Banco de Dados
   */
  public static function uncast(mixed $value, ?string $type = null): mixed
  {
    if ($value === null) {
      return null;
    }

    if ($value instanceof DateTime) {
      return $value->format('Y-m-d H:i:s');
    }

    if ($value instanceof \BackedEnum) {
      return $value->value;
    }

    return match ($type) {
      'json', 'array'  => is_array($value) || is_object($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value,
      'bool', 'boolean' => $value ? 1 : 0,
      default          => $value
    };
  }
}
