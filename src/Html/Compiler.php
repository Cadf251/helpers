<?php

namespace Cadud\Helpers\Html;

// use Cadud\Helpers\Html\ViewResponse;

class Compiler
{
  private string $basePath;

  public function __construct(string $basePath)
  {
    $this->basePath = rtrim($basePath, '/');
  }

  /**
   * Compila uma rota e salva o HTML estático no destino correto.
   *
   * @param string $route A rota acessada (ex: '/', '/sobre', '/lp/vendas')
   * @param ViewResponse $viewResponse O objeto da view com o HTML minificado
   * @return bool
   */
  public function compile(string $route, ViewResponse $viewResponse): bool
  {
    // Se a view veio com erro, aborta para não cachear página quebrada
    if ($viewResponse->withError) {
      return false;
    }

    $cleanRoute = trim($route, '/');

    // Determina o caminho exato do arquivo baseado na URL
    if ($cleanRoute === '') {
      // Se for a Home, vira .../index.html
      $targetFolder = $this->basePath;
      $targetFile = $targetFolder . '/index.html';
    } else {
      // Se for /sobre, vira .../sobre/index.html para o Apache/Nginx ler direto
      $targetFolder = $this->basePath . '/' . $cleanRoute;
      $targetFile = $targetFolder . '/index.html';
    }

    // Garante que a pasta da rota existe
    if (!is_dir($targetFolder)) {
      if (!mkdir($targetFolder, 0755, true) && !is_dir($targetFolder)) {
        return false;
      }
    }

    // Salva o HTML minificado no arquivo estático
    return file_put_contents($targetFile, $viewResponse->get()) !== false;
  }

  /**
   * Limpa o cache estático de uma rota específica ou de todo o site.
   */
  public function clear(?string $route = null): void
  {
    if ($route) {
      $cleanRoute = trim($route, '/');
      $targetFile = $this->basePath . '/' . ($cleanRoute === '' ? 'index.html' : $cleanRoute . '/index.html');
      if (file_exists($targetFile)) {
        unlink($targetFile);
      }
    } else {
      // Lógica opcional para varrer a public/ e limpar todos os index.html criados (limpeza total)
    }
  }
}
