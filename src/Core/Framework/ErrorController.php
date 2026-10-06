<?php

namespace Cadud\Helpers\Core\Framework;

use Cadud\Helpers\Core\Framework\Controller;
use Cadud\Helpers\Html\DTO\View;
use Throwable;

class ErrorController extends Controller
{
  public static string $viewName = "errors/default";

  /**
   * Instancia e executa o disparo do erro imediatamente
   */
  public function __construct(
    int $code = 500,
    ?string $message = null,
    ?Throwable $exception = null
  ) {
    http_response_code($code);

    $this->noIndex();

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

    $this->view = new View(self::$viewName);
    $this->view->head->title($title);

    $this->view->withData([
      "title"     => $title,
      "code"      => $code,
      "message"   => $errorMessage,
      "exception" => $exception
    ]);

    $this->view();
    exit();
  }

  /**
   * Permite invocar como função: (new ErrorController())(404, 'Mensagem')
   */
  public function __invoke(int $code = 500, ?string $message = null, ?Throwable $exception = null): void
  {
    // O próprio construtor já trata a renderização e o exit()
  }
}
