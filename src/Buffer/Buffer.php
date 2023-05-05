<?php

namespace Mpietrucha\Cli\Buffer;

use Closure;
use Exception;
use Illuminate\Support\Arr;
use Mpietrucha\Support\Macro;
use Mpietrucha\Support\Types;
use Illuminate\Support\Collection;
use Mpietrucha\Support\Pipeline;
use Mpietrucha\Support\Concerns\HasFactory;
use Mpietrucha\Cli\Contracts\BufferHandlerInterface;
use Mpietrucha\Cli\Buffer\Handlers\MultilineStringsHandler;
use Mpietrucha\Cli\Buffer\Handlers\SymfonyVarDumperHandler;

class Buffer
{
    use HasFactory;

    protected array $builder = [];

    protected static ?Collection $handlers = null;

    protected const HANDLERS = [
        MultilineStringsHandler::class,
        SymfonyVarDumperHandler::class
    ];

    public function __construct(protected ?Closure $callback = null, protected Output $output = new Output)
    {
        Macro::bootstrap();

        self::buildDefaultHandlers();

        ob_start($this->callback(...), 1);

        register_shutdown_function($this->flush(...));
    }

    public function __destruct()
    {
        $this->flush();
    }

    public static function addGlobalHandler(BufferHandlerInterface $handler): void
    {
        self::buildDefaultHandlers()->push($handler);
    }

    public static function createWithAppend(?string $left = null, ?string $right = null): self
    {
        return self::create(function (string $output) use ($left, $right) {
            return collect([$left, $output, $right])->toWord();
        });
    }

    public static function createWithNewLine(): self
    {
        return self::createWithAppend(right: PHP_EOL)->withoutHandler(MultilineStringsHandler::class);
    }

    public static function between(): void
    {
        Waiting::wait(1);
    }

    protected static function buildDefaultHandlers(): Collection
    {
        return self::$handlers ??= collect(self::HANDLERS)->map(fn (string $handler) => new $handler);
    }

    public function withoutHandler(string $name): self
    {
        return $this->builder(function (Collection $handlers) use ($name) {
            return $handlers->filter(fn (BufferHandlerInterface $handler) => $handler::class !== $name);
        });
    }

    public function withHandler(string|BufferHandlerInterface $handler): self
    {
        if (Types::string($handler) && Arr::in(self::HANDLERS, $handler)) {
            $handler = new $handler;
        }

        if (! $handler instanceof BufferHandlerInterface) {
            throw new Exception('Provider handler instance of BufferHandlerInterface');
        }

        return $this->builder(function (Collection $handlers) use ($handler) {
            return $handlers->push($handler);
        });
    }

    public function builder(Closure $callback): self
    {
        $this->builder[] = $callback;

        return $this;
    }

    public function handlersWithoutBuilder(): Collection
    {
        return $this->handlers(false);
    }

    public function handlers(bool $builder = true): Collection
    {
        return self::$handlers->unique()->when(count($this->builder) && $builder, function (Collection $handlers) {
            return $handlers->pipeThrough($this->builder);
        });
    }

    public function flush(): Output
    {
        return $this->output->flush(function () {
            $this->handlers()->each->after();

            ob_clean();
        });
    }

    protected function callback(string $output): string
    {
        $handlers = $this->handlers()->when($this->output->touched(), function (Collection $handlers) {
            $handlers->each->before();
        });

        if (! $output) {
            return $this->output->withRaw($output);
        }

        $passToCallback = Pipeline::create()
            ->send($output)
            ->through($handlers->toArray())
            ->thenReturn();

        if (! $passToCallback) {
            return $this->output->withRaw($output);
        }

        if ($processed = value($this->callback, $output)) {
            return $this->output->with($processed);
        }

        $this->output->withRaw($output);

        return '';
    }
}
