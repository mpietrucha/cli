<?php

namespace Mpietrucha\Cli\Concerns;

trait Ignorable
{
    protected bool $ignore = false;

    public function ignore(): void
    {
        $this->ignore = true;
    }
}
