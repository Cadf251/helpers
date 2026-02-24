<?php

namespace Cadud\Helpers\Core;

class Router
{
  private static $controller = null;
  private static $dev_only = null;

  private static $routes = [];

  public static function configController(string $controller)
  {
    self::$controller = $controller;
  }

  public static function configDevOnlyMiddleware(string $dev_only_host)
  {
    self::$dev_only = $dev_only_host;
  }

  public static function get(string $url, string $controller, string $method, string $middleware = 'guest')
  {
    self::add("GET", $url, $controller, $method, $middleware);
  }

  public static function post(string $url, string $controller, string $method, string $middleware = 'guest')
  {
    self::add("POST", $url, $controller, $method, $middleware);
  }

  private static function add(string $url_method, string $url, string $controller, string $method, string $middleware)
  {
    self::$routes[] = [
      'method'     => strtoupper($url_method),
      'url'        => trim($url, '/'),
      'controller' => $controller,
      'action'     => $method,
      'middleware' => $middleware
    ];
  }

  public static function run(string $url)
  {
    $url = trim($url, '/');
    $url = empty($url) ? 'index' : $url;
    $httpMethod = $_SERVER['REQUEST_METHOD'];

    foreach (self::$routes as $route) {
      if ($route['url'] === $url && $route['method'] === $httpMethod) {

        if (!self::checkAccess($route['middleware'])) {
          return class_exists('Controller') ? self::$controller::error(403) : die("Acesso negado");
        }

        $controller = $route['controller'];
        $action = $route['action'];

        if (class_exists($controller) && method_exists($controller, $action)) {
          return $controller::$action();
        }
      }
    }

    return class_exists('Controller') ? self::$controller::error(404) : die("Não encontrado");
  }

  public static function checkAccess(string $middleware)
  {
    if ($middleware === "guest") return true;
    if ($middleware === "dev") return $_SERVER['SERVER_NAME'] === self::$dev_only;
  }
}
