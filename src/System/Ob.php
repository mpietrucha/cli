<?php

namespace Mpietrucha\Cli\System;

class Ob
{
    public static function start(?Closure $handler = null, int $chunk = 0): void
    {
        ob_start($handler, $chunk);
    }

    public static function end(): void
    {
        if (! ob_get_length()) {
            return;
        }

        ob_end_flush();
    }
}
