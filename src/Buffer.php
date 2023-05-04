<?php

namespace Mpietrucha\Cli;

use Closure;
use Mpietrucha\Support\Types;
use Mpietrucha\Support\Waiting;
use Mpietrucha\Support\Concerns\HasFactory;

class Buffer
{
    use HasFactory;

    protected const SYMFONY_VAR_DUMPER_INDICATOR = 'Sfdump';

    public function __construct(Closure $callback, protected bool $skipSymfonyVarDumper = true)
    {
        ob_start(function (string $output) use ($callback) {
            if (! $output) {
                return $output;
            }

            if ($this->isSymfonyVarDumper($output)) {
                return $output;
            }

            if (Types::string($output = $callback($output))) {
                return $output;
            }

            return '';
        }, 1);
    }

    public static function explode(string $delimiter): self
    {
        return self::create(fn (string $output) => $output . $delimiter);
    }

    public static function newLine(): self
    {
        return self::explode(PHP_EOL);
    }

    public static function line():void
    {
        Waiting::wait(1);
    }

    public function flush(): void
    {
        ob_end_clean();
    }

    protected function isSymfonyVarDumper(string $output): bool
    {
        if (! $this->skipSymfonyVarDumper) {
            return false;
        }

        return str($output)->contains(self::SYMFONY_VAR_DUMPER_INDICATOR);
    }
}
