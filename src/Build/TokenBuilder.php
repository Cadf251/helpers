<?php

namespace Cadud\Helpers\Build;

class TokenBuilder
{
  private string $input;
  private string $dest;

  private array $cssVariables = [];
  private string $cssOutput = "";

  private array $extracted = [];

  public function __construct(string $input, string $dest)
  {
    $this->input = $input;
    $this->dest = $dest;
  }

  public function build(): bool
  {
    $jsonString = file_get_contents($this->input);
    if (!$jsonString) return false;
    
    $data = json_decode($jsonString, true);

    if (!$data) return false;

    $this->buildToken($data);

    return file_put_contents($this->dest, $this->cssOutput);
  }

  private function buildToken(array $data)
  {
    // Executa a extração
    $this->extractTokens($data);

    // 2. Segunda passada: Traduzir as referências do Figma {caminho.subcaminho} para var(--caminho-subcaminho)
    foreach ($this->extracted as $cssName => $value) {
      if (is_string($value) && strpos($value, '{') !== false) {
        // Transforma {palette.system.neutral-800} em var(--palette-system-neutral-800)
        $value = preg_replace_callback('/\{([^}]+)\}/', function ($matches) {
          $cleanReference = str_replace('.', '-', $matches[1]);
          return "var(--{$cleanReference})";
        }, $value);
      }

      // Adiciona ao array final formatado em CSS lowercase
      $this->cssVariables[strtolower($cssName)] = $value;
    }

    // 3. Monta a string final do arquivo CSS
    $this->cssOutput = ":root {\n";
    foreach ($this->cssVariables as $name => $value) {
      // Se o valor for fonte e tiver espaços, envolve em aspas
      // (Ignora as variáveis 'var(', cores hex '#', e funções 'rgba' geradas acima)
      if (strpos($value, ' ') !== false && strpos($value, 'var(') === false && strpos($value, '#') !== 0 && strpos($value, 'rgba') !== 0) {
        $value = "'$value'";
      }
      $this->cssOutput .= "  --{$name}: {$value};\n";
    }

    $this->cssOutput .= "}\n";
  }

  /**
   * Função recursiva para varrer o JSON e extrair os tokens
   */
  private function extractTokens(mixed $tokens, $prefix = '')
  {
    foreach ($tokens as $key => $token) {
      if (strpos($key, '$') === 0 && !in_array($key, ['$type', '$value'])) {
        continue;
      }

      // Monta o nome hierárquico da variável (ex: palette-system-second-700)
      $currentKey = $prefix ? $prefix . '-' . $key : $key;

      // Se encontrou a propriedade '$value', chegamos na ponta do token
      if (is_array($token) && isset($token['$value'])) {
        $value = $token['$value'];
        $type = $token['$type'] ?? 'string';

        // Tratamento específico para o tipo de dado
        if ($type === 'color' && is_array($value)) {
          // Se for cor e tiver Hex, usa o Hex (ignora os components srgb)
          $finalValue = $value['hex'] ?? '#000000';

          // Trata opacidade se o alpha for menor que 1
          if (isset($value['alpha']) && $value['alpha'] < 1) {
            // Utiliza o array de 'components' (RGB) que já vem no JSON para montar o RGBA
            if (isset($value['components']) && is_array($value['components'])) {
              $r = round($value['components'][0] * 255);
              $g = round($value['components'][1] * 255);
              $b = round($value['components'][2] * 255);
              $a = round($value['alpha'], 3); // Arredonda a opacidade para 3 casas decimais

              $finalValue = "rgba($r, $g, $b, $a)";
            } else {
              // Fallback: se não vier components, extrai RGB do Hexadecimal
              $hex = ltrim($finalValue, '#');
              if (strlen($hex) === 6) {
                $r = hexdec(substr($hex, 0, 2));
                $g = hexdec(substr($hex, 2, 2));
                $b = hexdec(substr($hex, 4, 2));
                $a = round($value['alpha'], 3);
                $finalValue = "rgba($r, $g, $b, $a)";
              }
            }
          }
        } elseif ($type === 'number') {
          // Se for número (gap, padding), adiciona 'px'
          $finalValue = $value . 'px';
        } else {
          // Strings simples (Fontes)
          $finalValue = is_string($value) ? trim($value) : $value;
        }

        $this->extracted[$currentKey] = $finalValue;
      }
      // Se for um array comum, continua descendo a árvore
      elseif (is_array($token)) {
        $this->extractTokens($token, $currentKey);
      }
    }
  }
}