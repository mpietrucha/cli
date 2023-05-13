<?php

namespace Mpietrucha\Cli\Concerns;

trait BufferHandlerIsIgnorable
{
    protected bool $ignore = false;

    public function ignore(): void
    {
        $this->ignore = true;
    }
}
