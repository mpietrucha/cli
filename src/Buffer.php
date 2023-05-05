<?php

namespace Mpietrucha\Cli;

use Closure;
use Mpietrucha\Support\Types;
use Mpietrucha\Support\Waiting;
use Mpietrucha\Support\Concerns\HasFactory;

class Buffer
{
    use HasFactory;

    protected bool $skipSymfonyVarDumper = true;

    protected bool $skipMultilineStrings = false;

    protected const SYMFONY_VAR_DUMPER_INDICATOR = 'Sfdump';

    public function __construct(protected Closure $callback)
    {
        ob_start($this->callback(...), 1);
    }

    public static function explode(string $delimiter): self
    {
        return self::create(function (string $output) use ($delimiter) {
            return "$output$delimiter";
        });
    }

    public static function newLine(): self
    {
        return self::explode(PHP_EOL)->skipMultilineStrings(true);
    }

    public static function line(): void
    {
        Waiting::wait(1);
    }

    public function skipSymfonyVarDumper(bool $mode): self
    {
        $this->skipSymfonyVarDumper = $mode;

        return $this;
    }

    public function skipMultilineStrings(bool $mode): self
    {
        $this->skipMultilineStrings = $mode;

        return $this;
    }

    public function flush(): void
    {
        ob_end_clean();
    }

    protected function callback(string $output): string
    {
        if (! $output) {
            return $output;
        }

        if ($this->isSymfonyVarDumper($output)) {
            return $output;
        }

        if ($this->isMultilineString($output)) {
            return $output;
        }

        if (Types::string($output = value($this->callback, $output))) {
            return $output;
        }

        return '';
    }

    protected function isSymfonyVarDumper(string $output): bool
    {
        if (! $this->skipSymfonyVarDumper) {
            return false;
        }

        return str($output)->contains(self::SYMFONY_VAR_DUMPER_INDICATOR);
     }

    protected function isMultilineString(string $output): bool
    {
        if (! $this->skipMultilineStrings) {
            return false;
        }

        return str($output)->contains(PHP_EOL);
    }
}
