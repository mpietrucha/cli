<?php

namespace Mpietrucha\Cli\Buffer\Handlers;

use Closure;
use Mpietrucha\Cli\Factory\BufferHandler;

class SymfonyVarDumperHandler extends BufferHandler
{
    public function before(): void
    {
    }

    public function after(): void
    {
    }

    public function handle(?string $output, Closure $next): ?string
    {
        return $next($output);
    }
}
