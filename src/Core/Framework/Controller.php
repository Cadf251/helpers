<?php

namespace Cadud\Helpers\Core\Framework;

use Cadud\Helpers\Cache\HTMLCompiler;
use Cadud\Helpers\Core\Config\AppConfig;
use Cadud\Helpers\Html\DTO\View;
use Cadud\Helpers\Html\DTO\ViewResponse;
use Cadud\Helpers\Html\Render\LoadLayout;

abstract class Controller
{
  protected View $view;
  protected ?string $layout = "main";
  protected string $route = "";
  protected bool $editor = false;
  protected bool $allowIndex = true;
  protected bool $cache = false;

  private static ?AppConfig $globalConfig = null;

  public static function setConfig(AppConfig $config): void
  {
    self::$globalConfig = $config;
  }

  public static function new(): static
  {
    return new static();
  }

  // --- Métodos Fluentes (Sempre retornam $this) ---

  public function editor(): static
  {
    $this->editor = true;
    return $this;
  }

  public function cache(string $route = ""): static
  {
    $this->route = $route;
    $this->cache = true;
    return $this;
  }

  public function noIndex(): static
  {
    $this->allowIndex = false;
    return $this;
  }

  public function layout(string $layout): static
  {
    $this->layout = $layout;
    return $this;
  }

  // --- Renderizador do Encadeamento ---

  protected function view(): mixed
  {
    $config = $this->getConfig();
    // Meta tag Canonical
    if (empty($this->view->head->canonical)) {
      $canonicalUrl = rtrim($config->baseUrl, '/') . '/' . ltrim($this->route, '/');
      $this->view->head->canonical($canonicalUrl);
    }

    // Meta tag Robots
    if (!$this->allowIndex) {
      $this->view->head->noIndex();
    }

    // Caminho físico do layout
    $viewResponse = LoadLayout::loadView($this->layout, $this->view->get());

    if ($viewResponse->withError) {
      $this->error(500);
    }

    // Se for export/editor, retorna o HTML puro para quem chamou
    if ($this->editor) {
      return $viewResponse->get();
    }

    // Compila o HTML em disco se o cache estiver habilitado
    if ($this->cache) {
      $this->cacheView($viewResponse);
    }

    $viewResponse->render();
    return null;
  }

  protected function cacheView(ViewResponse $view): void
  {
    $config = $this->getConfig();

    if ($config->cacheEnabled) {
      $compiler = new HTMLCompiler($config->rootDir . $config->cachePath);
      $compiler->compile($this->route, $view);
    }
  }

  protected function getConfig(): AppConfig
  {
    if (!self::$globalConfig) {
      self::$globalConfig = new AppConfig(
        baseUrl: $_ENV['APP_URL'] ?? 'http://localhost',
        rootDir: $_ENV['APP_ROOT'] ?? dirname(__DIR__, 3),
        cacheEnabled: filter_var($_ENV['CACHE_HTML'] ?? false, FILTER_VALIDATE_BOOLEAN)
      );
    }

    return self::$globalConfig;
  }

  public function error(int $code = 404): void
  {
    http_response_code($code);
    exit;
  }
}
