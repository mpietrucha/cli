<?php

namespace Mpietrucha\Cli\Buffer\Handlers;

use Closure;
use Mpietrucha\Cli\Cli;
use Mpietrucha\Support\Types;
use Mpietrucha\Cli\Buffer\Line;
use Mpietrucha\Cli\Buffer\Entry;
use Mpietrucha\Support\Resource;
use Mpietrucha\Cli\Concerns\BufferHandlerIsIgnorable;
use Mpietrucha\Cli\Concerns\BufferHandlerIsEncryptable;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class SymfonyVarDumperHandler extends AbstractHandler
{
    use BufferHandlerIsIgnorable;

    use BufferHandlerIsEncryptable;

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

        $this->setDefaultColors($this->getSupportsColors());

        $this->setOutput($this->output);
    }

    public function flushing(): ?Line
    {
        $line = $this->line();

        if (! $this->restore()) {
            return null;
        }

        $this->setDefaultColors(null);

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
        $output = $this->getCurrentOutput();

        if ($this->ignore) {
            return null;
        }

        if (! $output = $output->iterateContents(0)) {
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
        if (! $output = $this->getCurrentOutput()) {
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

        $this->setOutput($output);

        return true;
    }

    protected function setOutput(Resource $resource): void
    {
        $this->handler()::$defaultOutput = $resource->stream();
    }

    protected function setDefaultColors(?bool $defaultColors): void
    {
        $this->handler()::$defaultColors = $defaultColors;
    }

    protected function getCurrentOutput(): ?Resource
    {
        $output = $this->handler()::$defaultOutput;

        return Resource::build($output);
    }

    protected function handler(): string
    {
        return [CliDumper::class, HtmlDumper::class][! Cli::inside()];
    }
}
