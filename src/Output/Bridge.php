<?php

namespace Mpietrucha\Cli\Output;

use Mpietrucha\Cli\Concerns\Holdable;
use Mpietrucha\Cli\Contracts\HoldableInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

class Bridge implements OutputInterface, HoldableInterface
{
    use Holdable;

    public function __construct(protected OutputInterface $source)
    {
    }

    public function write(string|iterable $messages, bool $newline = false, int $options = 0)
    {
        $this->withHold(fn () => $this->source->write(
            $messages, $newline, $options
        ));
    }

    public function writeln(string|iterable $messages, int $options = 0)
    {
        $this->withHold(fn () => $this->source->writeln(
            $messages, $options
        ));
    }

    public function setVerbosity(int $level)
    {
        $this->source->setVerbosity($level);
    }

    public function getVerbosity(): int
    {
        return $this->source->getVerbosity();
    }

    public function isQuiet(): bool
    {
        return $this->source->isQuiet();
    }

    public function isVerbose(): bool
    {
        return $this->source->isVerbose();
    }

    public function isVeryVerbose(): bool
    {
        return $this->source->isVeryVerbose();
    }

    public function isDebug(): bool
    {
        return $this->source->isDebug();
    }

    public function setDecorated(bool $decorated)
    {
        $this->source->setDecorated($decorated);
    }

    public function isDecorated(): bool
    {
        return $this->source->isDecorated();
    }

    public function setFormatter(OutputFormatterInterface $formatter)
    {
        $this->source->getFormatter($formatter);
    }

    public function getFormatter(): OutputFormatterInterface
    {
        return $this->source->getFormatter();
    }
}
