<?php

namespace Mpietrucha\Cli\Contracts;

use Symfony\Component\HttpFoundation\Response;

interface ShouldFlushInterface
{
    public function flush(): ?Response;
}
