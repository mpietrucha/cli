<?php

namespace Mpietrucha\Cli\System;

use Closure;
use Mpietrucha\Error\Reporting;

class Ob
{
    public static function start(?Closure $handler = null, int $chunk = 0): void
    {
        ob_start($handler, $chunk);
    }

    public static function end(): void
    {
        Reporting::create()->withoutNotice()->while(fn () => ob_end_flush());
    }
}
