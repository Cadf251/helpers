<?php

namespace Cadud\Helpers\Email;

use Exception;
use PHPMailer\PHPMailer\PHPMailer as PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class Mailer
{
  private PHPMailer $mail;

  public function __construct(
    string $subject,
    string $body,
    string $to,
    string $toName = "",
    array $bodyParams = []
  )
  {
    try {
      $this->mail = new PHPMailer(true);
      $this->mail->SMTPDebug  = SMTP::DEBUG_OFF;
      $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
      $this->mail->isSMTP();
      $this->mail->SMTPAuth   = true;
      $this->mail->Host       = $_ENV["SMTP_HOST"];
      $this->mail->Username   = $_ENV["SMTP_USER"];
      $this->mail->Password   = $_ENV["SMTP_PASS"];
      $this->mail->Port       = $_ENV["SMTP_PORT"];
      $this->mail->isHTML(true);
      $this->mail->CharSet = 'UTF-8';
      $this->mail->setFrom($_ENV["SMTP_FROM"]);

      if (!empty($params)) {
        $body = $this->parameters($body, $bodyParams);
      }

      $this->setSubject($subject);
      $this->setBody($body);
      $this->addTo($to, $toName);
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), $e->getCode(), $e);
    }
  }

  public function addTo(string $to, string $name = '')
  {
    try {
      $this->mail->addAddress($to, $name);
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), $e->getCode(), $e);
    }
  }

  public function addImage(string $path, string $name)
  {
    try {
      $this->mail->addEmbeddedImage("path", "name");
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Subtitui os parâmetros na string para o email
   * 
   * @param string $body O Corpo do email sendo tratado
   * @param array $params Os parâmetros no formato [PARAM] => "var"
   * 
   * @return string O $body atualizado
   */
  public function parameters(string $body, array $params):string
  {
    foreach ($params as $key => $value){
      if (strpos($body, $key) !== false){
        $body = str_replace($key, $value, $body);
      }
    }
    return $body;
  }

  public function setSubject(string $subject)
  {
    
    try {
      $this->mail->Subject = $subject;
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), $e->getCode(), $e);
    }
  }

  public function setBody(string $body)
  {
    try {
      $this->mail->Body = $body;
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), $e->getCode(), $e);
    }
  }

  public function send()
  {
    try {
      $this->mail->send();
    } catch (Exception $e) {
      throw new Exception($e->getMessage(), $e->getCode(), $e);
    }
  }
}