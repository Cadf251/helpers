<?php

namespace Cadud\Helpers\Build;

use Exception;

class HTMLBuilder
{
  private array $dicionario = [];
  private string $html = '';
    
  private string $input;

  private string $dest;

  public function __construct(string $input, string $dest)
  {
    $this->input = $input;
    $this->dest = $dest;
  }

  public function build(): bool
  {
    $jsonRaw = file_get_contents($this->input);

    if($jsonRaw === false) {
      throw new Exception("Arquivo {$this->input} não é válido");
    };

    $app = json_decode($jsonRaw, true);

    if ($app === false) {
      throw new Exception(("Arquivo {$this->input} não pode ser decodificado"));
    }

    $this->html .= $this->buildHTML($app);
    
    // Put HTML
    $html = file_put_contents($this->dest . "HTML.php", $this->html);

    if (!$html) {
      throw new Exception(("HTML.php não foi salvo"));
    }

    // Put Texts
    $json = json_encode($this->dicionario, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    $texts = file_put_contents($this->dest . "texts.json", $json);
    
    if (!$texts) {
      throw new Exception(("Texts.json não foi salvo"));
    }
    
    return true;
  }

  private function buildHTML(array $node, int $depth = 0): string
  {
    $indent = str_repeat('  ', $depth);

    // 1. Se o elemento for ignorado, inserimos o comentário e encerramos este galho.
    if (!empty($node['skippedByOptimize'])) {
      $nome = $node['name'] ?? 'Sem nome';
      return "$indent<!-- Elemento ignorado: {$nome} -->\n";
    }

    $type = $node['type'] ?? 'FRAME'; // Assume FRAME como padrão
    $name = $node['name'] ?? '';

    // 2. Desempacotar a tag "App" (Root / Body)
    if ($name === 'App') {
      $innerHTML = '';
      if (isset($node['children']) && is_array($node['children'])) {
        foreach ($node['children'] as $child) {
          // Mantemos o $depth atual para a indentação não ir para frente sem motivo
          $innerHTML .= $this->buildHTML($child, $depth);
        }
      }
      return $innerHTML;
    }

    // ==========================================
    // TRATANDO TEXTOS, TAGS E CLASSES
    // ==========================================
    if ($type === 'TEXT') {
      $textContent = $node['characters'] ?? '';
      // 1. Remove caracteres de controle invisíveis (Zero-width spaces, byte order marks, etc)
      $textContent = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{2028}\x{2029}]/u', '', $textContent);

      // 2. Converte qualquer tipo de espaço Unicode (como NBSP e Narrow NBSP do Figma) para um espaço simples
      $textContent = preg_replace('/\p{Zs}/u', ' ', $textContent);

      // 3. Remove espaços extras que sobraram nas pontas
      $textContent = trim($textContent);

      // Considerando que $name segue a regra: tag.classe|chave (ex: h1.t1|home__hero--title)
      // 1. Separamos a string pelo "|"
      $parts = explode('|', $name);
      $tagAndClass = $parts[0]; // ex: "h1.t1", "p." ou ".description"
      $dictKey = $parts[1] ?? ''; // ex: "home__hero--title"

      // 2. Separamos a tag e a classe pelo "."
      $tcParts = explode('.', $tagAndClass);

      // Parte 1 (Tag): Se for vazio, assume 'span'
      $tag = !empty($tcParts[0]) ? strtolower($tcParts[0]) : 'span';

      // Parte 2 (Classe): Pega o que vier depois do "."
      $class = $tcParts[1] ?? '';

      // Monta o atributo de classe apenas se ele existir
      $classAttr = !empty($class) ? ", ['class' => '$class']" : '';

      // Parte 3 (Dicionário): Usa a chave definida após o "|"
      if (!empty($dictKey)) {
        $this->dicionario[$dictKey] = $textContent;
      }

      // O restante segue igual:
      return "$indent<?= tag('$tag', '$dictKey'{$classAttr}) ?>\n";
    }

    // Se for IMAGEM ou VETOR OU INSTANCIA
    if ($type === 'IMAGE' || $type === 'VECTOR' || $type === "INSTANCE") {
      // Depois podemos puxar a URL da imagem real, por enquanto deixamos um placeholder
      $alt = htmlspecialchars($name);
      return "$indent<img src=\"placeholder.jpg\" alt=\"{$alt}\" class=\"{$alt}\" />\n";
    }

    // 2. Preparamos a classe baseada no nome
    $classAttr = '';
    if (!empty($node['name'])) {
      // htmlspecialchars para garantir que as aspas do nome não quebrem o HTML
      $classAttr = ' class="' . htmlspecialchars($node['name']) . '"';
    }

    // 3. Processamos os filhos (recursividade)
    $innerHTML = '';
    if (isset($node['children']) && is_array($node['children'])) {
      foreach ($node['children'] as $child) {
        $innerHTML .= $this->buildHTML($child, $depth + 1);
      }
    }

    // 4. Montamos a tag diretamente no escopo (por enquanto tudo é div)
    return "$indent<div{$classAttr}>\n" . $innerHTML . $indent . "</div>\n";
  }
}
