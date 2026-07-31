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
    $expectedToken = "Bearer {$this->token}";

    if (!hash_equals($expectedToken, $authorization)) {
      $this->denyAccess('Acesso negado: Token inválido ou ausente.', 403);
    }

    // 3. Validar a Origem da Requisição (Domínios Permitidos)
    $allowedDomains = ['cdprado.dev', 'cdprado.local'];

    // Pega o Origin ou Referer enviado pelo navegador/cliente
    $originHeader = $headers['origin'] ?? $headers['referer'] ?? '';
    $hostHeader   = $_SERVER['HTTP_HOST'] ?? '';

    $originHost = '';
    if (!empty($originHeader)) {
      $originHost = parse_url($originHeader, PHP_URL_HOST) ?? '';
    } else {
      $originHost = strtok($hostHeader, ':'); // Remove porta, se existir (ex: cdprado.local:8080)
    }

    // Verifica se o host pertence a um dos domínios permitidos
    $isAllowed = false;
    foreach ($allowedDomains as $domain) {
      if ($originHost === $domain || str_ends_with($originHost, '.' . $domain)) {
        $isAllowed = true;
        break;
      }
    }

    if (!$isAllowed) {
      $this->denyAccess('Acesso negado: Origem não permitida.', 403);
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
