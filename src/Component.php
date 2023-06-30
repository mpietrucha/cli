<?php

namespace Mpietrucha\Cli;

use Mpietrucha\Support\Concerns\HasFactory;
use Symfony\Component\HttpFoundation\Response;
use Mpietrucha\Cli\Contracts\HoldableInterface;
use Mpietrucha\Cli\Contracts\ShouldFlushInterface;

abstract class Component implements ShouldFlushInterface, HoldableInterface
{
    use HasFactory;

    protected $finished = false;

    abstract protected function respond(): ?Response;

    public function __construct()
    {
        $this->registerFlushable();
    }

    public function __destruct()
    {
        $this->flush();
    }

    public function flush(): ?Response
    {
        if ($this->finished) {
            return null;
        }

        $this->finished = true;

        return $this->respond();
    }

    protected function registerFlushable(): void
    {
        System\Shutdown::register($this->flush(...));
    }
}
