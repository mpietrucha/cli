<?php

namespace Mpietrucha\Cli;

use Closure;
use Mpietrucha\Support\Macro;
use Mpietrucha\Support\Types;
use Mpietrucha\Support\Pipeline;
use Mpietrucha\Cli\Buffer\Result;
use Illuminate\Support\Collection;
use Mpietrucha\Support\Concerns\HasFactory;
use Mpietrucha\Cli\Concerns\BufferDefaultCreators;
use Mpietrucha\Cli\Contracts\BufferHandlerInterface;
use Mpietrucha\Cli\Buffer\Handlers\SymfonyVarDumperHandler;
use Mpietrucha\Cli\Buffer\Handlers\MultilineStringsHandler;

class Buffer
{
    use HasFactory;

    use BufferDefaultCreators;

    protected ?Collection $active = null;

    protected static ?Collection $handlers = null;

    protected const HANDLERS = [
        MultilineStringsHandler::class,
        SymfonyVarDumperHandler::class
    ];

    public function __construct(protected ?Closure $callback = null, protected Result $result = new Result)
    {
        Macro::bootstrap();

        ob_start($this->callback(...), 1);

        register_shutdown_function($this->flush(...));

        $this->active()->each->init();
    }

    public function __destruct()
    {
        $this->flush();
    }

    public static function between(): void
    {
        Waiting::wait(1);
    }

    public static function handler(BufferHandlerInterface $handler): void
    {
        self::handlers()->push($handler);
    }

    protected static function handlers(): Collection
    {
        return self::$handlers ??= collect(self::HANDLERS)->mapWithKeys(fn (string $handler) => [
            $handler => new $handler
        ]);
    }

    public function response(): ?string
    {
        return $this->active()->map->response()->filter()->toWord();
    }

    public function flush(): Result
    {
        return $this->result->flush(function () {
            $this->active()->each->flushing();

            ob_end_flush();

            $this->active()->each->flushed();

            self::$handlers = null;
        });
    }

    protected function callback(string $output): string
    {
        $handlers = $this->active()->when(! $this->result->touched(), function (Collection $handlers) {
            $handlers->each->touch();
        });

        if (! $output) {
            return $this->result->appendRaw($output);
        }

        $passToCallback = Pipeline::create()
            ->send($output)
            ->through($handlers->toArray())
            ->thenReturn();

        if (! $passToCallback) {
            return $this->result->appendRaw($output);
        }

        if (Types::string($processed = value($this->callback, $output))) {
            return $this->result->append($processed);
        }

        $this->result->appendRaw($output);

        return '';
    }

    protected function active(): Collection
    {
        return $this->active ??= self::handlers()->reject->disabled();
    }
}
