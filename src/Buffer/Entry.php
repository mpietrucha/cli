<?php

namespace Mpietrucha\Cli\Buffer;

use Illuminate\Support\Collection;
use Mpietrucha\Support\Types;
use Mpietrucha\Support\Condition;
use Mpietrucha\Support\Concerns\HasFactory;

class Entry
{
    use HasFactory;

    public function __construct(?string $entry = null, protected Collection $lines = new Collection)
    {
        $this->append($entry);
    }

    public static function fromCollection(Collection $lines): self
    {
        $instance = self::create();

        $lines->each(fn (null|string|Line $line) => $instance->append($line));

        return $instance;
    }

    public function prepend(null|string|Line $line): self
    {
        $this->lines->prepend($this->build($line));

        return $this;
    }

    public function append(null|string|Line $line): self
    {
        $this->lines->push($this->build($line));

        return $this;
    }

    public function lines(): Collection
    {
        return $this->lines->filter()->filter(fn (Line $line) => $line->get()->isNotEmpty());
    }

    protected function build(null|string|Line $line): ?Line
    {
        return Condition::create($line)->add(fn () => Line::create($line), Types::string($line))->resolve();
    }
}
