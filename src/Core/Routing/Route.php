<?php

namespace Cadud\Helpers\Core\Routing;

class Route
{
    public function __construct(
        public string $method,
        public string $path,
        public mixed $action,
        public array $middlewares = []
    ) {}
}