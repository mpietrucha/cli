<?php

namespace Mpietrucha\Cli\Contracts;

use Closure;

interface BufferHandlerInterface
{
    public function before(): void;

    public function after(): void;

    public function handle(?string $output, Closure $next): ?string;
}
