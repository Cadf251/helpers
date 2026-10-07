<?php

namespace Cadud\Helpers\Core\Framework;

use Cadud\Helpers\Html\Render\LoadLayout;
use Throwable;

class ErrorController
{
  public static string $path = "./views/errors/default.php";

  /**
   * Instancia e executa o disparo do erro imediatamente
   */
  public function __construct(
    int $code = 500,
    ?string $message = null,
    ?Throwable $exception = null
  ) {

    http_response_code($code);

    $defaultMessages = [
      400 => 'Requisição Inválida',
      403 => 'Acesso Negado',
      404 => 'Página Não Encontrada',
      405 => 'Método Não Permitido',
      500 => 'Erro Interno do Servidor',
      503 => 'Serviço Indisponível'
    ];

    $title = "Erro {$code}";
    $errorMessage = $message ?? ($defaultMessages[$code] ?? 'Ocorreu um erro inesperado');

    $response = LoadLayout::loadView(self::$path, [
      "title"     => $title,
      "code"      => $code,
      "message"   => $errorMessage,
      "exception" => $exception
    ]);

    if (!$response->withError) {
      $response->render();
    }

    exit;
  }

  /**
   * Permite invocar como função: (new ErrorController())(404, 'Mensagem')
   */
  public function __invoke(int $code = 500, ?string $message = null, ?Throwable $exception = null): void
  {
    // O próprio construtor já trata a renderização e o exit()
  }
}
