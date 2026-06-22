<?php

namespace Cadud\Helpers\Support;

/**
 * Orquestra o resultado de uma operação e serializa instruções
 * agnósticas para o frontend (PHP Puro, Laravel, AJAX ou Redirects).
 */
class OperationResult
{
  // Configurações globais padrão (PHP Puro)
  private static string $sessionKey = 'operation_result';
  private static ?string $default_display_mode = null;

  // Estado da Operação
  private string $status        = 'success'; // SUCCESS, WARNING, ERROR
  private string $statusName    = 'Sucesso!';
  private bool $reported = false;

  // Envelopes de dados finais
  private ?string $message      = null;
  private ?string $display_mode = null;
  private array $ui             = [];
  private array $data           = [];

  public function __construct()
  {
    // Define o modo visual inicial padrão do ecossistema
    $this->ui['updates'] = [];
  }

  /**
   * Configuração Global da inicialização do projeto (Bootstrap/ServiceProvider)
   */
  public static function config(array $settings): void
  {
    if (isset($settings['session_key'])) {
      self::$sessionKey = $settings['session_key'];
    }
    if (isset($settings['default_display_mode'])) {
      self::$default_display_mode = $settings['default_display_mode'];
    }
  }

  /** Define um modo de exibição alternativo direto no Controller */
  public function setMode(string $mode): self
  {
    $this->display_mode = $mode;
    return $this;
  }

  // -------------------------------------------------------------------------
  // Mutadores de Estado de Negócio
  // -------------------------------------------------------------------------

  public function warning(string $msg): self
  {
    $this->status = 'warning';
    $this->statusName = 'Atenção!';
    return $this->setMessage($msg);
  }

  public function failed(string $msg): self
  {
    $this->status = 'error';
    $this->statusName = 'Erro!';
    return $this->setMessage($msg);
  }

  public function setMessage(string $msg): self
  {
    $this->message = $msg;
    return $this;
  }

  public function hadFailed(): bool
  {
    return $this->status !== 'SUCCESS';
  }

  public function hadSucceeded(): bool
  {
    return !$this->hadFailed();
  }

  // -------------------------------------------------------------------------
  // Alimentadores das Gavetas 'UI' e 'DATA'
  // -------------------------------------------------------------------------

  public function withUi(array $uiInstructions): self
  {
    $this->ui = array_merge($this->ui, $uiInstructions);
    return $this;
  }

  public function withData(array $businessData): self
  {
    $this->data = array_merge($this->data, $businessData);
    return $this;
  }

  public function redirect(string $to): self
  {
    $this->ui["redirect"] = $to;
    return $this;
  }

  // -------------------------------------------------------------------------
  // Manipuladores de DOM Legados/Úteis (Embutidos no envelope UI)
  // -------------------------------------------------------------------------

  public function setUpdate(string $target, string $html): self
  {
    $this->ui['updates'][] = ['type' => 'update', 'target' => $target, 'html' => $html];
    return $this;
  }

  public function setAppend(string $target, string $html): self
  {
    $this->ui['updates'][] = ['type' => 'append', 'target' => $target, 'html' => $html];
    return $this;
  }

  public function setRemove(string $target): self
  {
    $this->ui['updates'][] = ['type' => 'remove', 'target' => $target];
    return $this;
  }

  // -------------------------------------------------------------------------
  // Motores de Resposta e Serialização (Agnósticos)
  // -------------------------------------------------------------------------

  public function toAjax(): array
  {
    $response = [
      'success'   => $this->hadSucceeded(),
      'message'   => null,
      'ui'        => $this->ui,
      'data'      => $this->data ?: null,
    ];

    if ($this->message && !$this->reported) {
      $response['message'] = [
        'display_mode' => $this->display_mode ?? self::$default_display_mode,
        'text'   => $this->message,
        'status' => $this->statusName,
      ];
    }

    return $response;
  }

  /**
   * Reporta para a sessão de forma híbrida (Suporta $_SESSION nativo e Laravel)
   */
  public function report(?string $message = null): self
  {
    if ($message) $this->setMessage($message);
    
    $sessionPayload = [
      'display_mode' => $this->display_mode ?? self::$default_display_mode,
      'text'         => $this->message,
      'status'       => $this->statusName,
    ];

    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $_SESSION[self::$sessionKey] = $sessionPayload;
    
    $this->reported = true;
    
    return $this;
  }
}
