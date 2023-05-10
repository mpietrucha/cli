<?php

namespace Mpietrucha\Cli\Buffer;

use Illuminate\Support\Stringable;
use Mpietrucha\Support\Concerns\HasFactory;

class Line
{
    use HasFactory;

    protected Stringable $line;

    protected bool $callback = true;

    public function __construct(string $line)
    {
        $this->line = str($line);
    }

    public function get(): Stringable
    {
        return $this->line;
    }

    public function shouldBePassedToCallback(?bool $mode = null): bool
    {
        if ($mode !== null) {
            $this->callback = $mode;
        }

        return $this->callback;
    }
}
