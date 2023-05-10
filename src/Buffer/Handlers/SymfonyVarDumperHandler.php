<?php

namespace Mpietrucha\Cli\Buffer\Handlers;

use Closure;
use Mpietrucha\Cli\Cli;
use Mpietrucha\Cli\Buffer\Line;
use Mpietrucha\Cli\Buffer\Entry;
use Mpietrucha\Support\Resource;
use Mpietrucha\Support\Condition;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class SymfonyVarDumperHandler extends AbstractHandler
{
    protected bool $ignore = false;

    protected ?Resource $saved = null;

    public function ignore(): void
    {
        $this->ignore = true;
    }

    public function init(): void
    {
        if (! $this->saved = $this->getCurrentOutput()) {
            return;
        }

        $this->setDefaultColors($this->getSupportsColors());

        $this->setOutput(Resource::create());
    }

    public function flushing(): ?Line
    {
        if (! $this->saved) {
            return null;
        }

        $line = $this->line();

        VarDumper::setHandler(null);

        $this->setOutput($this->saved);

        $this->setDefaultColors(null);

        return $line;
    }

    public function handle(Entry $entry, Closure $next): Entry
    {
        $entry = $next($entry);

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

        $output = $this->getCurrentOutput();

        $output->rewind();

        if (! $output = $output->iterateContents(0)) {
            return null;
        }

        $line = Line::create($output);

        $line->shuldBePassedToCallback(false);

        return $line;
    }

    protected function setOutput(Resource $resource): void
    {
        $this->handler()::$defaultOutput = $resource->stream();
    }

    protected function setDefaultColors(?bool $defaultColors): void
    {
        $this->handler()::$defaultColors = $defaultColors;
    }

    protected function getSupportsColors(): bool
    {
        $handler = $this->handler();

        return invade(new $handler)->supportsColors();
    }

    protected function getCurrentOutput(): ?Resource
    {
        $output = $this->handler()::$defaultOutput;

        return Resource::build($output);
    }

    protected function handler(): string
    {
        return Condition::create(CliDumper::class)->add(HtmlDumper::class, ! Cli::inside())->resolve();
    }
}
