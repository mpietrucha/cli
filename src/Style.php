<?php

namespace Mpietrucha\Cli;

use Closure;
use Mpietrucha\Support\Macro;
use Mpietrucha\Support\Concerns\HasFactory;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\OutputInterface;

class Style extends SymfonyStyle
{
    use HasFactory;

    protected bool $events = false;

    protected ?string $type = null;

    protected array $afterWrite = [];

    protected array $beforeWrite = [];

    protected ?string $currentType = null;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        Macro::bootstrap();

        parent::__construct($input, $output);
    }

    public static function link(string $url, ?string $anchor = null): string
    {
        return collect(['<href=', $url, '>', $anchor ?? $url, '</>'])->toWord();
    }

    public function type(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function withoutType(): self
    {
        return $this->type(null);
    }

    public function beforeWrite(Closure $callback): self
    {
        $this->beforeWrite[] = $callback;

        return $this;
    }

    public function afterWrite(Closure $callback): self
    {
        $this->afterWrite[] = $callback;

        return $this;
    }

    public function block(string|array $messages, string $type = null, string $style = null, string $prefix = ' ', bool $padding = false, bool $escape = true): void
    {
        $this->currentType = $type;

        parent::block($messages, $this->prefix ?? $type, $style, $prefix, $padding, $escape);
    }

    public function write(string|iterable $messages, bool $newline = false, int $options = 0): void
    {
        $this->withEvents(fn () => parent::write($messages, $newline, $options));
    }

    public function writeln(string|iterable $messages, int $options = 0): void
    {
        $this->withEvents(fn () => parent::writeLn($messages, $options));
    }

    public function definitionListWithSeparator(string|array ...$list): void
    {
        $list = collect($list)->mapOnArrayTo(fn (array $i) => [[$i, new TableSeparator]])->flatten(2);

        $list->when($list->last() instanceof TableSeparator, fn (Collection $list) => $list->pop());

        parent::definitionList(...$list->toArray());
    }

    protected function withEvents(Closure $handler): void
    {
        collect([
            fn () => $this->withEvent($this->beforeWrite),
            $handler,
            fn () => $this->withEvent($this->afterWrite)
        ])->each(fn (Closure $callback) => $callback());
    }

    protected function withEvent(array $events): void
    {
        if ($this->events) {
            return;
        }

        collect($events)->tap(fn () => $this->events = true)->each(fn (Closure $event) => $event(
            $this->currentType
        ))->tap(fn () => $this->events = false);
    }
}
