<?php

namespace Mpietrucha\Cli;

use Closure;
use Mpietrucha\Support\Concerns\HasFactory;

class Cli
{
    use HasFactory;

    public function __construct(protected Closure $output)
    {
    }
}
