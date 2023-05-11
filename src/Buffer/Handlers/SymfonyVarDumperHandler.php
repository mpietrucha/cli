<?php

namespace Mpietrucha\Cli\Buffer\Handlers;

use Closure;
use Mpietrucha\Cli\Cli;
use Mpietrucha\Support\Types;
use Mpietrucha\Support\Base64;
use Mpietrucha\Cli\Buffer\Line;
use Mpietrucha\Cli\Buffer\Entry;
use Mpietrucha\Support\Resource;
use Illuminate\Support\Collection;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class SymfonyVarDumperHandler extends AbstractHandler
{
    protected ?array $copy = null;

    protected bool $ignore = false;

    protected bool $encrypt = false;

    protected ?bool $supportsColors = null;

    protected string $encryptIndicator = self::DEFAULT_ENCRYPT_INDICATOR;

    protected const DEFAULT_ENCRYPT_INDICATOR = '__dump__';

    public function __construct(protected Resource $output = new Resource)
    {
    }

    public function output(Resource $output): void
    {
        $this->output = $output;
    }

    public function ignore(): void
    {
        $this->ignore = true;
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
            $this->build($output)
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
