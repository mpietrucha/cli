<?php

namespace Mpietrucha\Cli\Concerns;

use Closure;
use Illuminate\Support\Collection;

trait Holdable
{
    protected ?Collection $holders = null;

    public function hold(?Closure $while = null, ?object $instance = null): self
    {
        $this->holders = collect();

        if ($while) {
            $while->bindTo($instance ?? $this)();

            return $this->release();
        }

        return $this;
    }

    public function release(): self
    {
        $this->holders?->each(fn (Closure $event) => $event());

        return $this;
    }

    protected function withHold(Closure $event): void
    {
        if ($this->holders) {
            $this->holders->push($event);

            return;
        }

        $event();
    }
}
