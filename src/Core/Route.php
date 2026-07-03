<?php

namespace Cadud\Helpers\Core;

class Route
{
    public function __construct(
        public string $method,
        public string $path,
        public mixed $action,
        public array $middlewares = []
    ) {}
}