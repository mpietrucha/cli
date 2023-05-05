<?php

use Mpietrucha\Cli\Output;
use Mpietrucha\Cli\Buffer\Buffer;

if (! function_exists('output')) {
    function output(): Output {
        return Output::create();
    }
}

if (! function_exists('buffer')) {
    function buffer(Closure $callback): Buffer {
        return Buffer::create($callback);
    }
}
