<?php

namespace Cadud\Helpers\Cms;

use Cadud\Helpers\Cms\JsonRepository;
use Cadud\Helpers\Html\ViewResponse;

class CdpradoApiService
{
  public function __construct(
    private string $token,
  ) {}
  
  public function authorize(): void
  {
    // 1. Obter cabeçalhos HTTP de forma segura
    $headers = array_change_key_case(getallheaders() ?: [], CASE_LOWER);

    // 2. Validar o Token de Autorização (Bearer Token)
    $authorization = $headers['authorization'] ?? '';

    // Extrai apenas a string do token removendo o 'Bearer '
    $receivedToken = preg_replace('/^Bearer\s+/i', '', $authorization);

    $expectedHash = hash('sha256', $this->token);
    
    if (!hash_equals($expectedHash, $receivedToken)) {
      $this->denyAccess('Acesso negado: Token inválido ou ausente.', 403);
    }
  }

  /**
   * Interrompe a execução enviando o status e mensagem apropriados.
   */
  private function denyAccess(string $message, int $code = 403): void
  {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'success' => false,
      'error'   => $message
    ]);
    exit;
  }

  public function exportView(ViewResponse $view)
  {
    $this->authorize();

    echo json_encode([
      'success' => true,
      'html' => $view->get()
    ]);
    exit;
  }

  public function sync(JsonRepository $repository)
  {
    $this->authorize();

    $json = filter_input(INPUT_POST, "payload") ?? file_get_contents('php://input');

    if (empty($json)) {
      http_response_code(400); // 400 Bad Request é tecnicamente mais adequado que 500 para dados ausentes
      echo json_encode(['status' => 'failed', 'message' => 'Payload ausente']);
      exit;
    }

    $repository->save($json);
  }
}
