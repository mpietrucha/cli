<?php

namespace Mpietrucha\Cli;

use Closure;
use Illuminate\Support\Collection;
use Mpietrucha\Cli\Concerns\Creators;
use Mpietrucha\Cli\Concerns\Holdable;
use Symfony\Component\HttpFoundation\Response;

class Buffer extends Component
{
    use Creators;

    use Holdable;

    protected bool $tty = true;

    protected static ?array $configurator = null;

    public function __construct(protected ?Closure $callback = null, protected Collection $lines = new Collection)
    {
        System\Ob::start(function (string $line): string {
            $this->lines->push($line);

            if ($line && $this->tty) {
                $this->withHold(fn () => value($this->callback, $line));
            }

            return '';
        }, 1);

        parent::__construct();

        $this->configurator();
    }

    public static function configure(?Closure $configurator = null, array $arguments = []): self
    {
        self::$configurator = [$configurator, $arguments];

        return self::create();
    }

    public function callback(?Closure $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    public function tty(bool $mode = true): self
    {
        $this->tty = $mode;

        return $this;
    }

    public function quiet(): self
    {
        return $this->tty(false);
    }

    protected function respond(): ?Response
    {
        System\Ob::end();

        return new Response($this->lines->toNewLineWords());
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

        $this->callback($callback);

        self::$configurator = null;
    }
}
