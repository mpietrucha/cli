<?php

namespace Mpietrucha\Cli\Buffer;

use Closure;

class Output
{
    protected bool $touched = false;

    protected bool $flushed = false;

    protected ?string $raw = null;

    protected ?string $output = null;

    public function flush(Closure $callback): self
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

    public function with(string $output): string
    {
        $this->output .= $output;

        return $this->withRaw($output);
    }

    public function withRaw(string $output): string
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
