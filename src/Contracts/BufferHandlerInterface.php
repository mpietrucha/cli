<?php

namespace Mpietrucha\Cli\Contracts;

use Closure;
use Mpietrucha\Cli\Buffer\Line;
use Mpietrucha\Cli\Buffer\Entry;

interface BufferHandlerInterface
{
    public function disable(): void;

    public function disabled(): bool;

    public function init(): void;

    public function touching(): void;

    public function refreshing(): void;

    public function flushing(): ?Line;

    public function handle(Entry $entry, Closure $next): Entry;
}
