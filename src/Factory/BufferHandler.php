<?php

namespace Mpietrucha\Cli\Factory;

use Closure;
use Mpietrucha\Cli\Contracts\BufferHandlerInterface;

abstract class BufferHandler implements BufferHandlerInterface
{
    public function before(): void
    {
    }

    public function after(): void
    {
    }

    public function handle(?string $output, Closure $next): ?string
    {
        return $next($handler);
    }
}
