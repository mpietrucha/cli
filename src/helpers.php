<?php

use Mpietrucha\Cli\Cli;
use Mpietrucha\Cli\Buffer;

if (! function_exists('cli')) {
    function cli(): Cli {
        return Cli::create();
    }
}

if (! function_exists('buffer')) {
    function buffer(Closure $callback): Buffer {
        return Buffer::create($callback);
    }
}
