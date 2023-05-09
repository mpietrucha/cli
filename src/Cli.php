<?php

namespace Mpietrucha\Cli;

use Closure;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use Symfony\Component\HttpFoundation\Response;
use Mpietrucha\Support\Concerns\HasFactory;
use Mpietrucha\Support\Concerns\ForwardsCalls;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Cli
{
    use HasFactory;

    use ForwardsCalls;

    protected bool $end = false;

    protected ?Buffer $buffer = null;

    public function __construct()
    {
        $this->setInput(new ArrayInput([]));

        $this->setOutput($this->defaultOutput());

        $this->setStyle($this->defaultStyle());

        $this->forwardTo($this->style())->forwardThenReturnThis();
    }

    public function __destruct()
    {
        $this->end();
    }

    public static function inside(): bool
    {
        return php_sapi_name() === 'cli';
    }

    public function setInput(InputInterface $input): self
    {
        $this->input = $input;

        return $this;
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

    public function style(): SymfonyStyle
    {
        return $this->style;
    }

    public function buffer(Closure $handler, ?Closure $callback = null): self
    {
        $this->buffer = Buffer::create($handler->bindTo($this));

        value($callback, $this->buffer);

        return $this;
    }

    public function type(string $type): self
    {
        $this->style()->type($type);

        $this->style()->afterWrite(fn () => $this->style()->withoutType());

        return $this;
    }

    public function end(): void
    {
        if ($this->end) {
            return;
        }

        $this->end = true;

        $this->buffer?->flush();

        if (self::inside()) {
            return;
        }

        $response = new Response(
            with(new AnsiToHtmlConverter)->convert($this->output->fetch()) . $this->buffer->response()
        );

        $response->send();
    }

    protected function defaultOutput(): OutputInterface
    {
        if (self::inside()) {
            return new ConsoleOutput;
        }

        return new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);
    }

    protected function defaultStyle(): SymfonyStyle
    {
        return Style::create($this->input, $this->output);
    }
}
