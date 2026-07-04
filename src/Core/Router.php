<?php

namespace Cadud\Helpers\Core;

class Router
{
  private static array $staticRoutes = [];
  private static array $dynamicRoutes = [];
  private static array $middlewareRegistry = [];

  public static function get(string $path, callable $action, array|string $middlewares = [])
  {
    self::add('GET', $path, $action, (array)$middlewares);
  }

  public static function post(string $path, callable $action, array|string $middlewares = [])
  {
    self::add('POST', $path, $action, (array)$middlewares);
  }

  public static function registerMiddleware(string $key, callable $middleware)
  {
    self::$middlewareRegistry[$key] = $middleware;
  }

  private static function add(string $method, string $path, callable $action, array $middlewares)
  {
    $path = '/' . trim($path, '/');
    $route = new Route($method, $path, $action, $middlewares);

    if (str_contains($path, '{')) {
      // Converte {id} em uma regex capturável
      $regex = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([^/]+)', $path);
      $regex = '#^' . $regex . '$#';

      // Indexação tipo "SQL": Agrupa por Método -> Quantidade de segmentos
      $segmentCount = substr_count($path, '/');
      self::$dynamicRoutes[$method][$segmentCount][] = [
        'regex' => $regex,
        'route' => $route
      ];
    } else {
      // Indexação direta O(1) para rotas estáticas
      self::$staticRoutes[$method][$path] = $route;
    }
  }

  public static function run(?string $uri = null, ?string $method = null)
  {
    $uri = $uri ?? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    $method = $method ?? $_SERVER['REQUEST_METHOD'];

    $uri = '/' . trim($uri, '/');
    $method = strtoupper($method);

    // 1. Busca Direta (O(1) - Rápido como um índice de chave primária)
    if (isset(self::$staticRoutes[$method][$uri])) {
      return self::execute(self::$staticRoutes[$method][$uri]);
    }

    // 2. Busca Dinâmica Filtrada por Segmentos
    $segmentCount = substr_count($uri, '/');
    if (isset(self::$dynamicRoutes[$method][$segmentCount])) {
      foreach (self::$dynamicRoutes[$method][$segmentCount] as $dynamic) {
        if (preg_match($dynamic['regex'], $uri, $matches)) {
          array_shift($matches); // Remove a string completa do match
          return self::execute($dynamic['route'], $matches);
        }
      }
    }

    return self::handleError(404);
  }

  private static function execute(Route $route, array $params = [])
  {
    // Resolução dos middlewares em Pipeline (Cebola / Onion)
    $pipeline = function () use ($route, $params) {
      return ($route->action)(...$params);
    };

    // Executa os middlewares de trás para frente, envolvendo a action
    foreach (array_reverse($route->middlewares) as $middlewareKey) {
      if (!isset(self::$middlewareRegistry[$middlewareKey])) {
        return self::handleError(500, "Middleware {$middlewareKey} não registrado.");
      }

      $middleware = self::$middlewareRegistry[$middlewareKey];

      // Cada middleware recebe o próximo passo ($next) e os parâmetros
      $pipeline = function () use ($middleware, $pipeline, $params) {
        return $middleware($pipeline, ...$params);
      };
    }

    return $pipeline();
  }

  private static function handleError(int $code, string $message = '')
  {
    http_response_code($code);
    return ['error' => $code, 'message' => $message];
  }
}
