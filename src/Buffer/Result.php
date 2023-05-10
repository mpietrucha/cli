<?php

 namespace Mpietrucha\Cli\Buffer;

use Closure;
use Mpietrucha\Support\Types;
 use Illuminate\Support\Stringable;
 use Illuminate\Support\Collection;
 use Mpietrucha\Support\Argument;

class Result
{
    protected bool $tty = true;

    protected bool $flushed = false;

    protected ?Closure $transformer = null;

    public function __construct(protected Collection $entries = new Collection)
    {
    }

    public function tty(bool $mode): self
    {
        $this->tty = $mode;

        return $this;
    }

    public function flush(Closure $callback): self
    {
        if ($this->flushed()) {
            return $this;
        }

        $lines = $callback();

        if ($lines instanceof Collection) {
            $this->entry(Entry::fromCollection($lines));
        }

        if ($this->tty) {
            $this->output()->each(function (Closure $line) {
                echo value($line);
            });
        }

        $this->flushed  = true;

        return $this;
    }

    public function entry(Entry $entry): self
    {
        $this->entries->push($entry);

        return $this;
    }

    public function transformer(?Closure $transformer): self
    {
        $this->transformer = $transformer;

        return $this;
    }

    public function flushed(): bool
    {
        return $this->flushed;
    }

    public function touched(): bool
    {
        return $this->entries->count();
    }

    public function output(): Collection
    {
        return $this->entries->map($this->outputEntry(...))->flatten();
    }

    protected function outputEntry(Entry $entry): Collection
    {
        return $entry->lines()->map($this->outputLine(...))->filter();
    }

    protected function outputLine(Line $line): Closure
    {
        if (! $this->transformer || ! $line->shuldBePassedToCallback()) {
            return fn () => $line->get();
        }

        return function () use ($line): string {
            $line = value($this->transformer, $line->get());

            return Argument::create($line)->whenNotString(fn () => '')->value();
        };
    }
}
