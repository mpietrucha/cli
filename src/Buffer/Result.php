<?php

namespace Mpietrucha\Cli\Buffer;

use Closure;

class Result
{
    protected bool $touched = false;

    protected bool $flushed = false;

    protected ?string $raw = null;

    protected ?string $output = null;

    public function flush(?Closure $callback = null): self
    {
        if (! $this->flushed()) {
            value($callback, $this);
        }

        $this->flushed = true;

        return $this;
    }

    public function touch(): self
    {
        $this->touched = true;

        return $this;
    }

    public function touched(): bool
    {
        return $this->touched;
    }

    public function flushed(): bool
    {
        return $this->flushed;
    }

    public function append(string $output): string
    {
        $this->output .= $output;

        return $this->appendRaw($output);
    }

    public function appendRaw(string $output): string
    {
        $this->touch()->raw .= $output;

        return $output;
    }

    public function output(): ?string
    {
        return $this->output;
    }

    public function raw(): ?string
    {
        return $this->raw;
    }
}
