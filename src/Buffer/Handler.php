<?php

namespace Mpietrucha\Cli\Buffer;

use Closure;
use Mpietrucha\Cli\Contracts\BufferHandlerInterface;

abstract class Handler implements BufferHandlerInterface
{
    protected bool $disabled = false;

    public function disable(): void
    {
        $this->disabled = true;
    }

    public function disabled(): bool
    {
        return $this->disabled;
    }

    public function init(): void
    {
    }

    public function touch(): void
    {
    }

    public function flushing(): void
    {
    }

    public function flushed(): void
    {
    }

    public function response(): ?string
    {
        return null;
    }

    public function handle(?string $output, Closure $next): ?string
    {
        return $next($output);
    }
}
