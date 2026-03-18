<?php

namespace Cadud\Helpers\Core;

class Router
{
  private static $dev_only = null;
  private static bool $useSlash = false;
  private static $routes = [];

  /** @deprecated */ 
  public static function configController(?string $controller = null) {}

  public static function configUseSlash(bool $useSlash = true)
  {
    self::$useSlash = $useSlash;
  }

  public static function configDevOnlyMiddleware(string $dev_only_host)
  {
    self::$dev_only = $dev_only_host;
  }

  public static function get(string $url, $target, string $method = '', string $middleware = 'guest')
  {
    self::add("GET", $url, $target, $method, $middleware);
  }

  public static function post(string $url, $target, string $method = '', string $middleware = 'guest')
  {
    self::add("POST", $url, $target, $method, $middleware);
  }

  private static function add(string $url_method, string $url, $target, string $method = '', string $middleware = 'guest')
  {
    $url = self::prepareUrl($url, $url_method);

    self::$routes[$url] = [
      'target' => $target,
      'action'     => $method,
      'middleware' => $middleware
    ];
  }

  private static function prepareUrl(string $url, string $url_method): string
  {
    $url = trim($url, '/');
    $method = strtoupper($url_method);

    $key = $url . ":" . $method;

    if (self::$useSlash) {
      return "/$key";
    }
    return $key;
  }

  public static function run(string $url)
  {
    $httpMethod = $_SERVER['REQUEST_METHOD'];

    $url = self::prepareUrl($url, $httpMethod);

    if (isset(self::$routes[$url])) {
      $route = self::$routes[$url];

      if (!self::checkAccess($route['middleware'])) {
        return ["error" => 403];
      }

      $target = $route['target'];
      $action = $route['action'];

      if (is_callable($target)) {
        return $target();
      }

      if (class_exists($target) && method_exists($target, $action)) {
        return $target::$action();
      }
    }

    return ["error" => 404];
  }

  public static function checkAccess(string $middleware)
  {
    if ($middleware === "guest") return true;
    if ($middleware === "dev") return $_SERVER['SERVER_NAME'] === self::$dev_only;
  }
}
