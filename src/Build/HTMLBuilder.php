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

    $this->html .= $this->buildHeader();
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

  /**
   * Cabeçalho do arquivo gerado: explica para quem for editar (humano ou IA)
   * como interpretar os comentários de instrução espalhados pelo HTML.
   */
  private function buildHeader(): string
  {
    return "<!--\n"
      . "  ARQUIVO GERADO AUTOMATICAMENTE A PARTIR DO FIGMA (HTMLBuilder).\n"
      . "  Comentários no formato \"@IA [CATEGORIA] ...\" são instruções para o próximo passo:\n"
      . "  - Execute cada instrução no ponto exato em que o comentário aparece (ela se refere ao elemento logo abaixo).\n"
      . "  - Após concluir, remova o comentário da instrução.\n"
      . "  - Textos são renderizados via tag('<tag>', '<chave>', [atributos]) e o conteúdo fica em texts.json.\n"
      . "  - Quando houver 'figma-id', use-o para consultar o nó original no Figma.\n"
      . "  - Todos os containers foram gerados como <div>. Troque cada um pela tag semântica adequada ao seu papel\n"
      . "    (header, nav, main, section, article, aside, footer, ul/li, form, button), mantendo div apenas em wrappers de layout.\n"
      . "-->\n";
  }

  /**
   * Gera um comentário de instrução padronizado para ser interpretado por uma IA.
   */
  private function aiHint(string $indent, string $categoria, string $instrucao, array $node = []): string
  {
    $contexto = [];

    if (!empty($node['name'])) {
      $contexto[] = "nome: \"{$node['name']}\"";
    }
    if (!empty($node['type'])) {
      $contexto[] = "tipo: {$node['type']}";
    }
    if (!empty($node['id'])) {
      $contexto[] = "figma-id: {$node['id']}";
    }
    $tamanho = $this->describeSize($node);
    if ($tamanho !== '') {
      $contexto[] = "tamanho: {$tamanho}";
    }

    $texto = $instrucao . (!empty($contexto) ? ' (' . implode(', ', $contexto) . ')' : '');

    // Evita que o conteúdo feche o comentário (ou abra PHP) antes da hora
    $texto = str_replace(['<!--', '-->', '<?', '?>'], ['<!- -', '- ->', '< ?', '? >'], $texto);

    return "$indent<!-- @IA [{$categoria}] {$texto} -->\n";
  }

  /**
   * Retorna "LxA px" a partir das dimensões do nó (formato plugin ou REST API).
   */
  private function describeSize(array $node): string
  {
    $w = $node['width'] ?? $node['absoluteBoundingBox']['width'] ?? null;
    $h = $node['height'] ?? $node['absoluteBoundingBox']['height'] ?? null;

    if ($w === null || $h === null) {
      return '';
    }

    return round($w) . 'x' . round($h) . 'px';
  }

  /**
   * Nomes padrão do Figma ("Frame 12", "Group 3"...) não dizem nada sobre o elemento.
   */
  private function isGenericName(string $name): bool
  {
    return (bool) preg_match('/^(Frame|Group|Rectangle|Ellipse|Vector|Line|Container|Auto layout)( \d+)?$/i', trim($name));
  }

  /**
   * Converte o nome da camada em uma classe CSS válida (sem espaços/caracteres especiais).
   */
  private function toClassName(string $name): string
  {
    $class = preg_replace('/[^A-Za-z0-9_-]+/', '-', trim($name));
    return trim($class, '-');
  }

  private function buildHTML(array $node, int $depth = 0, bool $isRoot = true): string
  {
    $indent = str_repeat('  ', $depth);

    $type = $node['type'] ?? 'FRAME'; // Assume FRAME como padrão
    $name = $node['name'] ?? '';

    // 1. Se o elemento for ignorado, deixamos uma instrução para recriá-lo e encerramos este galho.
    if (!empty($node['skippedByOptimize'])) {
      return $this->aiHint(
        $indent,
        'RECRIAR',
        'Elemento omitido na exportação. Recrie-o aqui em HTML semântico seguindo o design do Figma, '
          . 'usando tag() para qualquer texto (registrando a chave em texts.json) e classes no mesmo padrão do restante do arquivo.',
        $node
      );
    }

    // 2. Desempacotar a tag "App" (Root / Body)
    if ($isRoot) {
      $innerHTML = '';
      if (isset($node['children']) && is_array($node['children'])) {
        foreach ($node['children'] as $child) {
          // Mantemos o $depth atual para a indentação não ir para frente sem motivo
          $innerHTML .= $this->buildHTML($child, $depth, false);
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

      // Instruções para o que a convenção de nome não conseguiu resolver
      $hints = '';
      $preview = mb_strimwidth($textContent, 0, 80, '...');

      // Sem "|chave": o texto não entra no dicionário e o tag() fica vazio
      if (empty($dictKey)) {
        $hints .= $this->aiHint(
          $indent,
          'TEXTO SEM CHAVE',
          "Crie uma chave no padrão pagina__secao--elemento, adicione-a em texts.json com o valor \"{$preview}\" "
            . 'e use-a como segundo argumento do tag() abaixo.',
          $node
        );
      }

      // Sem tag explícita: caiu no fallback 'span'
      if (empty($tcParts[0])) {
        $hints .= $this->aiHint(
          $indent,
          'TAG SEMÂNTICA',
          "Nenhuma tag definida no Figma, foi usado 'span'. Troque pela tag adequada ao papel do texto \"{$preview}\" "
            . '(h1-h6 para títulos, p para parágrafos, a para links, button para ações, label para formulários).',
          $node
        );
      }

      // Parte 3 (Dicionário): Usa a chave definida após o "|"
      if (!empty($dictKey)) {
        $this->dicionario[$dictKey] = $textContent;
      }

      // O restante segue igual:
      return $hints . "$indent<?= tag('$tag', '$dictKey'{$classAttr}) ?>\n";
    }

    // Se for IMAGEM ou VETOR OU INSTANCIA
    if ($type === 'IMAGE' || $type === 'VECTOR' || $type === "INSTANCE") {
      $alt = htmlspecialchars($name);
      $class = htmlspecialchars($this->toClassName($name));

      $instrucoes = [
        'IMAGE' => 'Exporte a imagem do Figma (WebP, 2x) para a pasta de assets, troque "placeholder.jpg" pelo caminho real, '
          . 'escreva um alt que descreva o conteúdo e defina width/height para evitar layout shift.',
        'VECTOR' => 'Isto é um vetor: substitua a <img> abaixo por um SVG inline (ou arquivo .svg em assets) exportado do Figma. '
          . 'Se for decorativo, use aria-hidden="true"; se transmitir significado, adicione <title> ou aria-label.',
        'INSTANCE' => 'Isto é uma instância de componente do Figma: substitua a <img> abaixo pela marcação do componente. '
          . 'Se ele já existir no projeto, reutilize-o; senão, crie-o como componente/partial reutilizável.',
      ];

      return $this->aiHint($indent, 'SUBSTITUIR PLACEHOLDER', $instrucoes[$type], $node)
        . "$indent<img src=\"placeholder.jpg\" alt=\"{$alt}\" class=\"{$class}\" />\n";
    }

    // 2. Preparamos a classe baseada no nome
    $hints = '';
    $classAttr = '';
    if (!empty($name)) {
      $class = $this->toClassName($name);
      // htmlspecialchars para garantir que as aspas do nome não quebrem o HTML
      $classAttr = ' class="' . htmlspecialchars($class) . '"';

      if ($this->isGenericName($name)) {
        $hints .= $this->aiHint(
          $indent,
          'RENOMEAR CLASSE',
          "A classe \"{$class}\" veio de um nome genérico do Figma. Renomeie-a com base no conteúdo interno "
            . '(ex: home__hero, card__header) e atualize o CSS correspondente.',
          $node
        );
      }
    } else {
      $hints .= $this->aiHint(
        $indent,
        'NOMEAR CLASSE',
        'Container sem nome no Figma, gerado sem classe. Adicione uma classe descritiva com base no conteúdo interno.',
        $node
      );
    }

    // Frame sem filhos: provavelmente é um shape decorativo (fundo, divisor, badge)
    if (empty($node['children'])) {
      $hints .= $this->aiHint(
        $indent,
        'ELEMENTO VAZIO',
        'Container sem conteúdo, provavelmente decorativo (fundo, divisor, forma). Se for só visual, resolva via CSS '
          . '(::before/::after ou background) e remova esta div; se tiver função, adicione o conteúdo que falta.',
        $node
      );
    }

    // 3. Processamos os filhos (recursividade)
    $innerHTML = '';
    if (isset($node['children']) && is_array($node['children'])) {
      foreach ($node['children'] as $child) {
        $innerHTML .= $this->buildHTML($child, $depth + 1);
      }
    }

    // 4. Montamos a tag diretamente no escopo (por enquanto tudo é div, a troca semântica é instruída no cabeçalho)
    return $hints . "$indent<div{$classAttr}>\n" . $innerHTML . $indent . "</div>\n";
  }
}
