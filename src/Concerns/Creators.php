<?php

namespace Mpietrucha\Cli\Concerns;

use Closure;
use Mpietrucha\Cli\Buffer\Handlers\SymfonyVarDumperHandler;

trait Creators
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

    public static function createWithoutSymfonyVarDumper(?Closure $callback = null): self
    {
        return self::configure(function () use ($callback) {
            $this->handlers()->get(SymfonyVarDumperHandler::class)->ignore();

            return $callback;
        });
    }
}
