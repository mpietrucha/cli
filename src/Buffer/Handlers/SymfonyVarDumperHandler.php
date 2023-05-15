<?php

namespace Mpietrucha\Cli\Buffer\Handlers;

use Closure;
use Mpietrucha\Cli\Cli;
use Mpietrucha\Support\Types;
use Mpietrucha\Cli\Buffer\Line;
use Mpietrucha\Cli\Buffer\Entry;
use Mpietrucha\Support\Resource;
use Mpietrucha\Cli\Concerns\Ignorable;
use Mpietrucha\Cli\Concerns\Encryptable;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class SymfonyVarDumperHandler extends AbstractHandler
{
    use Ignorable;

    use Encryptable;

    protected ?array $copy = null;

    protected ?bool $supportsColors = null;

    protected const DEFAULT_ENCRYPT_INDICATOR = '__dump__';

    public function __construct(protected Resource $output = new Resource)
    {
        $this->encryptableIndicator(self::DEFAULT_ENCRYPT_INDICATOR);
    }

    public function output(Resource $output): void
    {
        $this->output = $output;
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

        $handler = $this->handler();

        return invade(new $handler)->supportsColors();
    }

    public function init(): void
    {
        if (! $this->copy()) {
            return;
        }

        $this->setHandlerDefaultColors($this->getSupportsColors());

        $this->setHandlerOutput($this->output);
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

        $this->copy = [$output, VarDumper::setHandler(null)];

        return true;
    }

    protected function restore(): bool
    {
        [$output, $handler] = $this->copy ?? [null, null];

        if (! $output) {
            return false;
        }

        VarDumper::setHandler($handler);

        $this->setHandlerOutput($output);

        return true;
    }

    protected function setHandlerDefaultColors(?bool $defaultColors): void
    {
        $this->handler()::$defaultColors = $defaultColors;
    }

    protected function setHandlerOutput(Resource $resource): void
    {
        $this->handler()::$defaultOutput = $resource->stream();
    }

    protected function getHandlerOutput(): ?Resource
    {
        $output = $this->handler()::$defaultOutput;

        return Resource::build($output);
    }

    protected function handler(): string
    {
        return [CliDumper::class, HtmlDumper::class][! Cli::inside()];
    }
}
