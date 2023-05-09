<?php

namespace Mpietrucha\Cli\Contracts;

use Closure;

interface BufferHandlerInterface
{
    public function disable(): void;

    public function disabled(): bool;

    public function init(): void;

    public function touch(): void;

    public function flushing(): void;

    public function flushed(): void;

    public function response(): ?string;

    public function handle(?string $output, Closure $next): ?string;
}
