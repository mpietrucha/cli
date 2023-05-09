<?php

namespace Mpietrucha\Cli\Concerns;

use Mpietrucha\Cli\Buffer\Handlers\MultilineStringsHandler;

trait BufferDefaultCreators
{
    public static function createWithAppend(string $append): self
    {
        return self::create(function (string $output) use ($append) {
            return "$output$append";
        });
    }

    public static function createWithPrepend(string $prepend): self
    {
        return self::create(function (string $output) use ($prepend) {
            return "$prepend$output";
        });
    }

    public static function createWithNewLine(): self
    {
        self::handlers()->get(MultilineStringsHandler::class)->disable();

        return self::createWithAppend(PHP_EOL);
    }
}
