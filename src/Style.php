<?php

namespace Mpietrucha\Cli;

use Closure;
use Mpietrucha\Support\Macro;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Style extends SymfonyStyle
{
    protected ?string $type = null;

    protected ?string $prefix = null;

    protected ?Closure $afterWrite = null;

    protected ?Closure $beforeWrite = null;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        Macro::bootstrap();

        parent::__construct($input, $output);
    }

    public function prefix(?string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function clearPrefix(): self
    {
        return $this->prefix(null);
    }

    public function beforeWrite(Closure $callback): self
    {
        $this->beforeWrite = $callback;

        return $this;
    }

    public function afterWrite(Closure $callback): self
    {
        $this->afterWrite = $callback;

        return $this;
    }

    public function block(string|array $messages, string $type = null, string $style = null, string $prefix = ' ', bool $padding = false, bool $escape = true): void
    {
        $this->type = $type;

        parent::block($messages, $this->prefix ?? $type, $style, $prefix, $padding, $escape);
    }

    public function write(string|iterable $messages, bool $newline = false, int $options = 0): void
    {
        $this->events(fn () => parent::write($messages, $newline, $options));
    }

    public function writeln(string|iterable $messages, int $options = 0): void
    {
        $this->events(fn () => parent::writeLn($messages, $options));
    }

    public function definitionListWithSeparator(string|array ...$list): void
    {
        $list = collect($list)->mapOnArrayTo(fn (array $i) => [[$i, new TableSeparator]])->flatten(2);

        $list->when($list->last() instanceof TableSeparator, fn (Collection $list) => $list->pop());

        parent::definitionList(...$list->toArray());
    }

    public function link(string $url, ?string $anchor = null): string
    {
        return collect(['<href=', $url, '>', $anchor ?? $url, '</>'])->toWord();
    }

    protected function events(Closure $middle): void
    {
        collect([$this->beforeWrite, $middle, $this->afterWrite])->filter()->each(fn (Closure $callback) => $callback(
            $this->type
        ));
    }
}
