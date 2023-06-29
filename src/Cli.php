<?php

namespace Mpietrucha\Cli;

use Closure;
use Termwind\Terminal;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use Symfony\Component\HttpFoundation\Response;
use Mpietrucha\Support\Concerns\ForwardsCalls;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Mpietrucha\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Mpietrucha\Error\Concerns\InteractsWithErrorHandler;

class Cli extends Component
{
    use ForwardsCalls;

    use InteractsWithErrorHandler;

    protected ?Html $html = null;

    protected SymfonyStyle $style;

    protected InputInterface $input;

    protected OutputInterface $output;

    protected bool $convert = true;

    protected ?Buffer $buffer = null;

    protected bool $shouldRespond = false;

    public function __construct(array $input = [])
    {
        $this->setInput($this->defaultInput($input));

        $this->setOutput(self::defaultOutput());

        $this->setStyle($this->defaultStyle());

        $this->forwardTo($this->style())->forwardThenReturnThis();

        parent::__construct();
    }

    public static function inside(): bool
    {
        return \PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg';
    }

    public static function defaultOutput(): OutputInterface
    {
        if (self::inside()) {
            return new ConsoleOutput;
        }

        return new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);
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

    public function html(): Html
    {
        return $this->html ??= Html::create($this->output);
    }

    public function terminal(): Terminal
    {
        return new Terminal;
    }

    public function buffer(Closure $configurator): self
    {
        $buffer = Buffer::configure($configurator, [$this]);

        return $this->setBuffer($buffer);
    }

    public function getBuffer(?Closure $configurator = null): Buffer
    {
        if ($this->buffer) {
            return $this->buffer;
        }

        throw_unless($configurator, new InvalidArgumentException(
            'Configurator must be instanceof', [Closure::class]
        ));

        return $this->buffer($configurator)->getBuffer();
    }

    public function as(string $as): self
    {
        $this->style()->as($as)->afterWrite(function () {
            $this->style()->asDefault();
        });

        return $this;
    }

    public function shouldRespond(bool $mode = true): self
    {
        $this->shouldRespond = $mode;

        return $this;
    }

    public function convert(bool $mode = true): self
    {
        $this->convert = $mode;

        return $this;
    }

    public function raw(): self
    {
        return $this->convert(false);
    }

    protected function respond(): ?Response
    {
        $this->buffer?->flush();

        if (self::inside()) {
            return null;
        }

        $response = new Response($this->defaultContents());

        if ($this->shouldRespond) {
            $response->send();
        }

        return $response;
    }

    protected function defaultInput(array $input): InputInterface
    {
        return new ArrayInput($input);
    }

    protected function defaultStyle(): SymfonyStyle
    {
        return Style::create($this->input, $this->output);
    }

    protected function defaultContents(): string
    {
        if ($contents = $this->output->fetch() && ! $this->convert) {
            return $contents;
        }

        $converter = new AnsiToHtmlConverter();

        return $converter->convert($contents);
    }
}
