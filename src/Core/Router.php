<?php

namespace Cadud\Helpers\Core;

class Router
{
  private array $staticRoutes = [];
  private array $dynamicRoutes = [];
  private array $middlewareRegistry = [];

  public function get(string $path, callable $action, array|string $middlewares = []): self
  {
    return $this->add('GET', $path, $action, (array)$middlewares);
  }

  public function post(string $path, callable $action, array|string $middlewares = []): self
  {
    return $this->add('POST', $path, $action, (array)$middlewares);
  }

  public function registerMiddleware(string $key, callable $middleware): self
  {
    $this->middlewareRegistry[$key] = $middleware;
    return $this;
  }

  private function add(string $method, string $path, callable $action, array $middlewares): self
  {
    $path = '/' . trim($path, '/');
    $route = new Route($method, $path, $action, $middlewares);

    if (str_contains($path, '{')) {
      // Converte {id} em uma regex capturável
      $regex = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $path);
      $regex = '#^' . $regex . '$#';

      // Indexação tipo "SQL": Agrupa por Método -> Quantidade de segmentos
      $segmentCount = substr_count($path, '/');
      $this->dynamicRoutes[$method][$segmentCount][] = [
        'regex' => $regex,
        'route' => $route
      ];
    } else {
      // Indexação direta O(1) para rotas estáticas
      $this->staticRoutes[$method][$path] = $route;
    }

    return $this;
  }

  public function run(?string $uri = null, ?string $method = null)
  {
    $uri = $uri ?? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    $method = $method ?? $_SERVER['REQUEST_METHOD'];

    $uri = '/' . trim($uri, '/');
    $method = strtoupper($method);

    // 1. Busca Direta (O(1) - Rápido como um índice de chave primária)
    if (isset($this->staticRoutes[$method][$uri])) {
      return $this->execute($this->staticRoutes[$method][$uri]);
    }

    // 2. Busca Dinâmica Filtrada por Segmentos
    $segmentCount = substr_count($uri, '/');
    if (isset($this->dynamicRoutes[$method][$segmentCount])) {
      foreach ($this->dynamicRoutes[$method][$segmentCount] as $dynamic) {
        if (preg_match($dynamic['regex'], $uri, $matches)) {
          array_shift($matches); // Remove a string completa do match
          return $this->execute($dynamic['route'], $matches);
        }
      }
    }

    return $this->handleError(404);
  }

  private function execute(Route $route, array $params = [])
  {
    // Resolução dos middlewares em Pipeline (Cebola / Onion)
    $pipeline = function () use ($route, $params) {
      return ($route->action)(...$params);
    };

    // Executa os middlewares de trás para frente, envolvendo a action
    foreach (array_reverse($route->middlewares) as $middlewareKey) {
      if (!isset($this->middlewareRegistry[$middlewareKey])) {
        return $this->handleError(500, "Middleware {$middlewareKey} não registrado.");
      }

      $middleware = $this->middlewareRegistry[$middlewareKey];

      // Cada middleware recebe o próximo passo ($next) e os parâmetros
      $pipeline = function () use ($middleware, $pipeline, $params) {
        return $middleware($pipeline, ...$params);
      };
    }

    return $pipeline();
  }

  private function handleError(int $code, string $message = '')
  {
    http_response_code($code);
    return ['error' => $code, 'message' => $message];
  }
}
