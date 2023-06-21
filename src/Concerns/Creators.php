<?php

namespace Mpietrucha\Cli\Concerns;

use Closure;

trait Creators
{
    public static function createWithDelimiter(string $delimiter, ?Closure $configurator = null): self
    {
        return self::configure($configurator)->callback(function (string $line) use ($delimiter) {
            return "$line$delimiter";
        });
    }

    public static function createWithNewLine(?Closure $configurator = null): self
    {
        return self::createWithDelimiter(PHP_EOL, $configurator);
    }
}
