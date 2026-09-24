<?php

namespace Cadud\Helpers\Cache;

class ENVCompiler
{
  private string $basePath;

  public function __construct(string $basePath)
  {
    $this->basePath = rtrim($basePath, '/');
  }

  public function compile()
  {
    $envData = $_ENV;

    // Constrói a string de código PHP válido
    $code = "<?php\n";
    $code .= "// Gerado automaticamente via Compiler - " . date('Y-m-d H:i:s') . "\n";
    $code .= "return " . var_export($envData, true) . ";\n";

    // Garante que o diretório existe
    $dir = dirname($this->basePath);
    if (!is_dir($dir)) {
      mkdir($dir, 0755, true);
    }

    // Salva o arquivo no disco de forma atômica
    return file_put_contents($this->basePath, $code) !== false; 
  }

  public function update()
  {
    $this->clear();
    $this->compile();
  }

  public function clear(): bool
  {
    if (file_exists($this->basePath)) {
      return unlink($this->basePath);
    }

    return true;
  }
}
