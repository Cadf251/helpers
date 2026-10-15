<?php

namespace Cadud\Helpers\Storage;

abstract class JsonFileHandler
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
   * Gets the full content or a specific key
   */
  public function get(?string $key = null, mixed $default = null)
  {
    return $key === null ? $this->data : $this->data[$key] ?? $default;
  }

  /**
   * Overwrite the content
   */
  public function update(array $data)
  {
    $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);

    $this->save($json);
  }

  /**
   * Updates the JSON file with the content in the Database
   */
  public function save(string $json): void
  {
    file_put_contents($this->file, $json);
  }

  /**
   * Deletes the JSON file
   */
  public function delete()
  {
    unlink($this->file);
  }
}