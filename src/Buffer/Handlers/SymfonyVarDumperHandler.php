<?php

namespace Mpietrucha\Cli\Buffer\Handlers;

use Closure;
use Mpietrucha\Cli\Cli;
use Mpietrucha\Support\Types;
use Mpietrucha\Cli\Buffer\Line;
use Mpietrucha\Cli\Buffer\Entry;
use Mpietrucha\Support\Package;
use Mpietrucha\Support\Resource;
use Illuminate\Support\Collection;
use Mpietrucha\Cli\Concerns\Ignorable;
use Mpietrucha\Cli\Concerns\Encryptable;
use Symfony\Component\VarDumper\VarDumper;
use Illuminate\Foundation\Console\CliDumper as LaravelCliDumper;
use Illuminate\Foundation\Console\HtmDumper as LaravelHtmlDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper as SymfonyCliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper as SymfonyHtmlDumper;

class SymfonyVarDumperHandler extends AbstractHandler
{
    use Ignorable;

    use Encryptable;

    protected ?array $copy = null;

    protected ?string $handler = null;

    protected ?bool $supportsColors = null;

    protected const DEFAULT_ENCRYPT_INDICATOR = '__dump__';

    public function __construct(protected Resource $output = new Resource, protected Collection $handlers = new Collection)
    {
        $this->encryptableIndicator(self::DEFAULT_ENCRYPT_INDICATOR);

        $this->handler(LaravelHtmlDumper::class);
        $this->handler(SymfonyHtmlDumper::class);

        $this->handler(LaravelHtmlDumper::class, true);
        $this->handler(SymfonyCliDumper::class, true);
    }

    public function output(Resource $output): void
    {
        $this->output = $output;
    }

    public function handler(string $handler, bool $console = false): void
    {
        $this->handlers->list($console, $handler);
    }

    public function supportsColors(?bool $mode = true): void
    {
        $this->supportsColors = $mode;
    }

    public function getSupportsColors(): bool
    {
        if (! Types::null($this->supportsColors)) {
            return $this->supportsColors;
        }

        $handler = $this->getHandler();

        return invade(new $handler)->supportsColors();
    }

    public function init(): void
    {
        if (! $this->getHandler()) {
            return;
        }

        if (! $this->copy()) {
            return;
        }

        $this->setHandlerDefaultColors($this->getSupportsColors());

        $this->setHandlerOutput($this->output);
    }

    public function refreshing(): void
    {
        $this->setHandler();
    }

    public function flushing(): ?Line
    {
        $line = $this->line();

        if (! $this->restore()) {
            return null;
        }

        $this->setHandlerDefaultColors(null);

        return $line;
    }

    public function handle(Entry $entry, Closure $next): Entry
    {
        $entry = Entry::fromCollection(
            $next($entry)->lines()->map(fn (Line $line) => $this->decrypt($line))->flatten()
        );

        if (! $line = $this->line()) {
            return $entry;
        }

        return $entry->prepend($line);
    }

    protected function line(): ?Line
    {
        if ($this->ignore) {
            return null;
        }

        $output = $this->output->iterateContents(0);

        if (! $output) {
            return null;
        }

        $line = Line::create(
            $this->encrypt($output)
        );

        $line->shouldBePassedToCallback(false);

        return $line;
    }

    protected function copy(): bool
    {
        if (! $output = $this->getHandlerOutput()) {
            return false;
        }

        $this->copy = [$output, $this->setHandler()];

        return true;
    }

    protected function restore(): bool
    {
        [$output, $handler] = $this->copy ?? [null, null];

        if (! $output) {
            return false;
        }

        $this->setHandler($handler);

        $this->setHandlerOutput($output);

        return true;
    }

    protected function setHandler(?Closure $handler = null): ?Closure
    {
        return VarDumper::setHandler($handler);
    }

    protected function setHandlerDefaultColors(?bool $defaultColors): void
    {
        $this->getHandler()::$defaultColors = $defaultColors;
    }

    protected function setHandlerOutput(Resource $resource): void
    {
        $this->getHandler()::$defaultOutput = $resource->stream();
    }

    protected function getHandlerOutput(): ?Resource
    {
        $output = $this->getHandler()::$defaultOutput;

        return Resource::build($output);
    }

    protected function getHandler(): ?string
    {
        if ($this->handler) {
            return $this->handler;
        }

        $handlers = $this->handlers->get(Cli::inside());

        if (! $handlers) {
            return null;
        }

        return $this->handler = $handlers->first(function (string $handler) {
            return Package::exists($handler);
        });
    }
}
