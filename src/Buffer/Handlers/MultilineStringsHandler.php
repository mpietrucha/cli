<?php

namespace Mpietrucha\Cli\Buffer\Handlers;

use Closure;
use Mpietrucha\Cli\Buffer\Handler;

class MultilineStringsHandler extends Handler
{
    public function handle(?string $output, Closure $next): ?string
    {
        if (str($output)->contains(PHP_EOL)) {
            return null;
        }

        return $next($output);
    }
}
