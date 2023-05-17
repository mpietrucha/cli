<?php

namespace Mpietrucha\Cli;

use Termwind\Question;
use Termwind\Termwind;
use Termwind\HtmlRenderer;
use Termwind\Repositories\Style;
use Termwind\Repositories\Styles;
use Mpietrucha\Support\Concerns\HasFactory;
use Symfony\Component\Console\Output\OutputInterface;

class Html
{
    use HasFactory;

    public function __construct(OutputInterface $output)
    {
        Termwind::renderUsing($output);
    }

    public function render(string $contents, int $options = OutputInterface::OUTPUT_NORMAL): void
    {
        with(new HtmlRenderer)->render($contents, $options);
    }

    public function ask(string $question, iterable $autocomplete = null): mixed
    {
        return with(new Question)->ask($question, $autocomplete);
    }

    public function style(string $name, Closure $callback = null): Style
    {
        return Styles::create($name, $callback);
    }
}
