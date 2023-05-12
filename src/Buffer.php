<?php

namespace Mpietrucha\Cli;

use Closure;
use Exception;
use Mpietrucha\Support\Macro;
use Illuminate\Support\Sleep;
use Mpietrucha\Cli\Buffer\Entry;
use Mpietrucha\Cli\Buffer\Result;
use Mpietrucha\Support\Pipeline;
use Illuminate\Support\Collection;
use Mpietrucha\Cli\Concerns\BufferCreators;
use Mpietrucha\Support\Concerns\HasFactory;
use Mpietrucha\Cli\Contracts\BufferHandlerInterface;
use Mpietrucha\Cli\Buffer\Handlers\SymfonyVarDumperHandler;
use Mpietrucha\Cli\Buffer\Handlers\ExplodeMultilineStringsHandler;

class Buffer
{
    use HasFactory;

    use BufferCreators;

    protected ?Collection $handlers = null;

    protected static ?array $configurator = null;

    protected const HANDLERS = [
        SymfonyVarDumperHandler::class,
        ExplodeMultilineStringsHandler::class,
    ];

    public function __construct(?Closure $callback = null, protected Result $result = new Result)
    {
        Macro::bootstrap();

        $this->setCallback($callback)->configurator();

        ob_start($this->callback(...), 1);

        register_shutdown_function($this->flush(...));

        $this->handlers()->each->init();

        self::$configurator = null;
    }

    public function __destruct()
    {
        $this->flush();
    }

    public static function configure(?Closure $configurator = null, array $arguments = []): self
    {
        self::$configurator = [$configurator, $arguments];

        return self::create();
    }

    public static function eol(): void
    {
        Sleep::for(1)->millisecond();
    }

    public function setCallback(?Closure $callback): self
    {
        if ($this->result->touched()) {
            throw new Exception('Cannot set callback while buffer is already started');
        }

        $this->result->transformer($callback);

        return $this;
    }

    public function handlers(): Collection
    {
        $this->handlers ??= collect(self::HANDLERS)->mapWithKeysToInstance();

        return $this->handlers->reject->disabled();
    }

    public function handler(BufferHandlerInterface $handler): self
    {
        $this->handlers()->push($handler);

        return $this;
    }

    public function tty(bool $mode = true): self
    {
        $this->result->tty($mode);

        return $this;
    }

    public function flush(): Result
    {
        return $this->result->flush(function () {
            ob_end_flush();

            return $this->handlers()->map->flushing()->filter();
        });
    }

    protected function callback(string $output): string
    {
        $this->lines($output);

        return '';
    }

    protected function lines(string $output): void
    {
        if (! $output) {
            return;
        }

        $entry = Entry::create($output);

        if (! $this->handlers()->count()) {
            $this->result->entry($entry);

            return;
        }

        $handlers = $this->handlers()->when(! $this->result->touched(), function (Collection $handlers) {
            $handlers->each->touch();
        });

        $this->result->entry(Pipeline::create()
            ->send($entry)
            ->through($handlers->toArray())
            ->thenReturn());
    }

    protected function configurator(): void
    {
        if (! self::$configurator) {
            return;
        }

        [$configurator, $arguments] = self::$configurator;

        $callback = value($configurator?->bindTo($this), ...$arguments);

        if (! $callback instanceof Closure) {
            return;
        }

        $this->setCallback($callback);
    }
}
