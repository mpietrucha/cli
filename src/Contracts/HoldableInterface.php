<?php

namespace Mpietrucha\Cli\Contracts;

use Closure;

interface HoldableInterface
{
    public function hold(?Closure $event = null): self;

    public function release(): self;
}
