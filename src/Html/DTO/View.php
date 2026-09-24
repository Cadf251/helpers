<?php

namespace Cadud\Helpers\Html\DTO;

final class View
{
    public ViewHead $head;
    public array $nav = [];
    public string $name = "";
    public array $data = [];
    public bool $withError = false;

    public function __construct(string $name = "", array $data = [])
    {
        $this->head = new ViewHead();
        $this->name = $name;
        $this->data = $data;
    }

    public function withData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function withNav(array $nav): static
    {
        $this->nav = $nav;
        return $this;
    }

    public function withError(bool $withError = true): static
    {
        $this->withError = $withError;
        return $this;
    }

    /**
     * Array plano pronto pra extract()/renderPartial, mantendo
     * compatibilidade com o formato atual ($view["head"], $view["nav"]...).
     */
    public function get(): array
    {
        return [
            "head" => $this->head->toArray(),
            "nav" => $this->nav,
            "name" => $this->name,
            "data" => $this->data,
            "withError" => $this->withError,
        ];
    }
}