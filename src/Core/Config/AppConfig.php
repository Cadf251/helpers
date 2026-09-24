<?php

namespace Cadud\Helpers\Core\Config;

class AppConfig
{
  public function __construct(
    public readonly string $baseUrl,
    public readonly string $rootDir,
    public readonly bool $cacheEnabled = false,
    public readonly string $cachePath = '/public/html'
  ) {}
}