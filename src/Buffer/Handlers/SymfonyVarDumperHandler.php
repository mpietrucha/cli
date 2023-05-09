<?php

namespace Mpietrucha\Cli\Buffer\Handlers;

use Mpietrucha\Cli\Cli;
use Mpietrucha\Support\Types;
use Mpietrucha\Support\Resource;
use Mpietrucha\Support\Condition;
use Mpietrucha\Cli\Buffer\Handler;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

class SymfonyVarDumperHandler extends Handler
{
    protected bool $ignore = false;

    protected ?string $response = null;

    protected ?Resource $saved = null;

    public function ignore(): self
    {
        $this->ignore = true;

        return $this;
    }

    public function init(): void
    {
        if (! $this->saved = $this->getCurrentOutput()) {
            return;
        }

        $this->setDefaultColors($this->getSupportsColors());

        $this->setOutput(Resource::create());
    }

    public function flushed(): void
    {
        if (! $this->saved) {
            return;
        }

        $output = $this->getCurrentOutput();

        VarDumper::setHandler(null);

        $this->setOutput($this->saved);

        $this->setDefaultColors(null);

        if ($this->ignore) {
            return;
        }

        $output->rewind();

        if (! Cli::inside()) {
            $this->response = $output->getContents();

            return;
        }

        $this->saved->write($output->getContents());
    }

    public function response(): ?string
    {
        return $this->response;
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
