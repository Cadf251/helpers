<?php

namespace Cadud\Helpers\Cms;

abstract class JsonRepository
{
  private array $data;

  public function __construct(
    private string $file
  ) {
    $this->load();
  }

  /**
   * Loads the JSON file
   */
  public function load(): void
  {
    if (empty($this->data)) {
      $content = file_get_contents($this->file);
      $this->data = json_decode($content, true) ?? [];
    }
  }

  /**
   * Gets the content
   */
  public function get()
  {
    return $this->data;
  }

  /**
   * Updates the JSON file with the content in the Database
   */
  public function save(string $json): void
  {
    file_put_contents($this->file, $json);
  }
}