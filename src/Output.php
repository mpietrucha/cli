<?php

namespace Mpietrucha\Cli;

use Closure;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use Mpietrucha\Support\Concerns\HasFactory;
use Mpietrucha\Support\Concerns\ForwardsCalls;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;

class Output
{
    use HasFactory;

    use ForwardsCalls;

    protected bool $end = false;

    protected ?Buffer $buffer = null;

    public function __construct()
    {
        $this->setOutput($this->defaultOutput());

        $this->setStyle($this->defaultStyle());

        $this->forwardTo($this->style())->forwardThenReturnThis();
    }

    public function __destruct()
    {
        $this->end();
    }

    public static function isRunningInConsole(): bool
    {
        return php_sapi_name() === 'cli';
    }

    public function buffer(Closure $callback, ?Closure $callback = null): self
    {
        $this->buffer = Buffer::create($callback);

        value($callback, $this->buffer);

        return $this;
    }

    public function style(): SymfonyStyle
    {
        return $this->style;
    }

    public function with(string $prefix): self
    {
        $this->style()->prefix($prefix);

        $this->style()->afterWrite(fn () => $this->style()->clearPrefix());

        return $this;
    }

    public function end(): void
    {
        if ($this->end) {
            return;
        }

        $this->end = true;

        $this->buffer?->flush();

        if (self::isRunningInConsole()) {
            return;
        }

        $response = new Response(
            with(new AnsiToHtmlConverter())->convert($this->output->fetch())
        );

        $response->send();
    }

    public function setOutput(OutputInterface $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function setStyle(SymfonyStyle $style): self
    {
        $this->style = $style;

        return $this;
    }

    protected function defaultOutput(): OutputInterface
    {
        if (self::isRunningInConsole()) {
            return new ConsoleOutput;
        }

        return new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);
    }

    protected function defaultStyle(): SymfonyStyle
    {
        return new Style(new ArrayInput([]), $this->output);
    }
}
