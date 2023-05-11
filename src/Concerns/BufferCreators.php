<?php

namespace Mpietrucha\Cli\Concerns;

use Closure;

trait BufferCreators
{
    public static function createWithDelimiter(string $delimiter, ?Closure $configurator = null): self
    {
        return self::configure($configurator)->setCallback(function (string $output) use ($delimiter) {
            return "$output$delimiter";
        });
    }

    public static function createWithNewLine(?Closure $configurator = null): self
    {
        return self::createWithDelimiter(PHP_EOL, $configurator);
    }
}
