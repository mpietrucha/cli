<?php

namespace Mpietrucha\Cli\Buffer\Handlers;

use Closure;
use Mpietrucha\Cli\Cli;
use Mpietrucha\Support\Types;
use Mpietrucha\Support\Base64;
use Mpietrucha\Cli\Buffer\Line;
use Mpietrucha\Cli\Buffer\Entry;
use Mpietrucha\Support\Resource;
use Mpietrucha\Support\Condition;
use Illuminate\Support\Collection;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class SymfonyVarDumperHandler extends AbstractHandler
{
    protected bool $ignore = false;

    protected bool $encrypt = false;

    protected ?Resource $saved = null;

    protected ?bool $supportsColors = null;

    protected string $encryptIndicator = self::ENCRYPT_INDICATOR;

    protected const ENCRYPT_INDICATOR = '__dump__';

    public function ignore(): void
    {
        $this->ignore = true;
    }

    public function supportsColors(?bool $mode = true): void
    {
        $this->supportsColors = $mode;
    }

    public function encrypt(?string $indicator = null): void
    {
        if ($indicator) {
            $this->encryptIndicator($indicator);
        }

        $this->encrypt = true;
    }

    public function encryptIndicator(string $indicator): void
    {
        $this->encryptIndicator = $indicator;
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

        $output = $this->getCurrentOutput();

        $output->rewind();

        if (! $output = $output->iterateContents(0)) {
            return null;
        }

        $line = Line::create($this->build($output));

        $line->shouldBePassedToCallback(false);

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
        if (! Types::null($this->supportsColors)) {
            return $this->supportsColors;
        }

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

    protected function build(string $output): string
    {
        if (! $this->encrypt) {
            return $output;
        }

        return collect([
            $this->encryptIndicator, Base64::encode($output), $this->encryptIndicator
        ])->toWord();
    }

    protected function decrypt(Line $line): Collection|Line
    {
        if (! $line->get()->contains($this->encryptIndicator)) {
            return $line;
        }

        $hits = $line->get()->toBetweenCollection($this->encryptIndicator);

        $line = $line->get()->remove([
            ...$hits, $this->encryptIndicator
        ]);

        $hits = $hits->map(fn (string $hit) => Base64::decode($hit))->mapInto(Line::class)->tap(function (Collection $lines) {
            $lines->each->shouldBePassedToCallback(false);
        });

        return collect([
            Line::create($line), ...$hits
        ]);
    }
}
