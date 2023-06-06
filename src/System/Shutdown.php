<?php

namespace Mpietrucha\Cli\System;

use Closure;

class Shutdown
{
    public static function register(Closure $handler): void
    {
        register_shutdown_function($handler);
    }
}
