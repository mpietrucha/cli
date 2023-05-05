<?php

namespace Mpietrucha\Cli\Buffer\Handlers;

use Closure;
use Mpietrucha\Cli\Factory\BufferHandler;

class MultilineStringsHandler extends BufferHandler
{
    public function handle(?string $output, Closure $next): ?string
    {
        if (str($output)->contains(PHP_EOL)) {
            return null;
        }

        return $next($output);
    }
}
