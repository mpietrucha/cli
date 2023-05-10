<?php

namespace Mpietrucha\Cli;

use Closure;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Input\ArrayInput;
use Mpietrucha\Support\Concerns\HasFactory;
use Mpietrucha\Support\Concerns\ForwardsCalls;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Cli
{
    use HasFactory;

    use ForwardsCalls;

    protected SymfonyStyle $style;

    protected InputInterface $input;

    protected OutputInterface $output;

    protected bool $finished = false;

    protected ?Buffer $buffer = null;

    public function __construct(array $input = [])
    {
        $this->setInput($this->defaultInput($input));

        $this->setOutput($this->defaultOutput());

        $this->setStyle($this->defaultStyle());

        $this->forwardTo($this->style())->forwardThenReturnThis();

        register_shutdown_function($this->finish(...));
    }

    public function __destruct()
    {
        $this->finish();
    }

    public static function inside(): bool
    {
        return \PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg';
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

    public function setBuffer(Buffer $buffer): self
    {
        $this->buffer = $buffer;

        return $this;
    }

    public function style(): SymfonyStyle
    {
        return $this->style;
    }

    public function buffer(Closure $configurator): self
    {
        $buffer = Buffer::configure($configurator, [$this]);

        return $this->setBuffer($buffer);
    }

    public function type(string $type): self
    {
        $this->style()->type($type);

        $this->style()->afterWrite(fn () => $this->style()->withoutType());

        return $this;
    }

    public function finish(): void
    {
        if ($this->finished) {
            return;
        }

        $this->finished = true;

        $this->buffer?->flush();

        if (self::inside()) {
            return;
        }
    }

    protected function defaultInput(array $input): InputInterface
    {
        return new ArrayInput($input);
    }

    protected function defaultOutput(): OutputInterface
    {
        if (! self::inside()) {
            return new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);
        }

        return new ConsoleOutput;
    }

    protected function defaultStyle(): SymfonyStyle
    {
        return Style::create($this->input, $this->output);
    }
}
