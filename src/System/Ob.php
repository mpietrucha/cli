<?php

namespace Mpietrucha\Cli\System;

use Closure;

class Ob
{
    public static function start(?Closure $handler = null, int $chunk = 0): void
    {
        ob_start($handler, $chunk);
    }

    public static function end(): void
    {
        ob_end_flush();
    }
}
