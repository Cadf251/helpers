<?php

namespace Cadud\Helpers\Core;

class Router
{
  /** @var array $routes Para rotas fixas */
  private static array $routes = [];

  /** @var array $magicRoutes Para rodas dinâmicas */
  private static array $magicRoutes = [];

  private static array $middlewares = [];

  /** @deprecated */
  public static function configController(?string $controller = null) {}

  /** @deprecated */
  public static function configUseSlash(bool $useSlash = true) {}

  /** @deprecated */
  private static $dev_only = null;

  /** @deprecated */
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

  public static function addMiddleware(string $key, callable $func)
  {
    self::$middlewares[$key] = $func;
  }

  private static function add(string $url_method, string $url, $target, string $method = '', string $middleware = 'guest')
  {
    $url = self::prepareUrl($url);
    $method = self::prepareMethod($method);

    $data = [
      'target'      => $target,
      'action'      => $method,
      'middleware'  => $middleware
    ];

    if (self::isMagic($url)) {

      self::$magicRoutes[$url_method][] = [
        'url' => $url,
        ...$data
      ];
    } else {
      self::$routes[$url_method][$url] = $data;
    }
  }

  private static function prepareUrl(string $url): string
  {
    return trim($url, '/');
  }

  private static function prepareMethod(string $method): string
  {
    return strtoupper($method);
  }

  public static function isMagic(string $url)
  {
    return str_contains($url, '{');
  }

  public static function run(string $url)
  {
    $url = self::prepareUrl($url);
    $httpMethod = self::prepareMethod($_SERVER['REQUEST_METHOD']);

    if (isset(self::$routes[$httpMethod][$url])) {
      $route = self::$routes[$httpMethod][$url];

      return self::execute($route);
    }

    if (isset(self::$magicRoutes[$httpMethod])) {
      // Partes da URL que está sendo rodada
      $urlParts = explode('/', $url);

      // Contagem de slices
      $urlCount = count($urlParts);

      foreach (self::$magicRoutes[$httpMethod] as $route) {
        // Partes da rota que está sendo verificada
        $pathParts = explode('/', $route['url']);

        // Se o número de fatias for diferente, essa rota não serve
        if (count($pathParts) !== $urlCount) {
          continue;
        }

        $params = [];
        $match = true;

        // Compara fatia por fatia
        foreach ($pathParts as $index => $part) {
          if (self::isMagic($part)) {
            // É um parametro mágico {}, guarde o parametro no array
            $params[] = $urlParts[$index];
          } else if ($part !== $urlParts[$index]) {

            // É um texto fixo (ex: "blog") e não bate com a URL? não serve também
            $match = false;
            break;
          }
        }

        // Se chegamos aqui e $match continua true, a rota foi encontrada
        if ($match) {
          return self::execute($route, $params);
        }
      }
    }

    return ["error" => 404];
  }

  /**
   * O Motor único de execução
   */
  private static function execute(array $route, array $params = [])
  {
    if (!self::checkAccess($route['middleware'])) {
      return ["error" => 403];
    }

    $target = $route['target'];
    $action = $route['action'];

    if (is_callable($target)) {
      return call_user_func_array($target, $params);
    }

    if (class_exists($target) && method_exists($target, $action)) {
      return $target::$action();
    }

    return ["error" => 404];
  }

  public static function checkAccess(string $key): bool
  {
    if ($key === "guest") return true;

    return $middle[$key] ?? false;
  }

  /**
   * Returns all the routes in array format
   */
  public static function debug()
  {
    return [
      "comum routes" => self::$routes,
      "magic routes" => self::$magicRoutes
    ];
  }
}
