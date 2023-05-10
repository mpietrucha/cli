<?php

namespace Mpietrucha\Cli\Buffer\Handlers;

use Closure;
use Mpietrucha\Cli\Buffer\Entry;
use Mpietrucha\Cli\Buffer\Line;
use Mpietrucha\Cli\Contracts\BufferHandlerInterface;

abstract class AbstractHandler implements BufferHandlerInterface
{
    protected bool $disabled = false;

    public function disable(): void
    {
        $this->disabled = true;
    }

    public function disabled(): bool
    {
        return $this->disabled;
    }

    public function init(): void
    {
    }

    public function touch(): void
    {
    }

    public function flushing(): ?Line
    {
        return null;
    }

    public function handle(Entry $entry, Closure $next): Entry
    {
        return $next($entry);
    }
}
