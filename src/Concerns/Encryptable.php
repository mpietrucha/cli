<?php

namespace Mpietrucha\Cli\Concerns;

use Mpietrucha\Cli\Cli;
use Mpietrucha\Support\Base64;
use Illuminate\Support\Collection;
use Mpietrucha\Cli\Buffer\Line;

trait Encryptable
{
    protected bool $encryptable = false;

    protected ?string $encryptableIndicator = null;

    public function encryptable(?string $indicator = null): void
    {
        if ($indicator) {
            $this->encryptableIndicator($indicator);
        }

        $this->encryptable = true;
    }

    public function encryptableIndicator(string $indicator): void
    {
        $this->encryptableIndicator = $indicator;
    }

    protected function encrypt(string $output): string
    {
        if (! Cli::inside()) {
            return $output;
        }

        if (! $this->encryptable || ! $this->encryptableIndicator) {
            return $output;
        }

        return collect([
            $this->encryptableIndicator, Base64::encode($output), $this->encryptableIndicator
        ])->toWord();
    }

    protected function decrypt(Line $line): Collection|Line
    {
        if (! $this->encryptableIndicator) {
            return $line;
        }

        if (! $line->get()->contains($this->encryptableIndicator)) {
            return $line;
        }

        $hits = $line->get()->toBetweenCollection($this->encryptableIndicator);

        $line = $line->get()->remove([
            ...$hits, $this->encryptableIndicator
        ]);

        $hits = $hits->map(fn (string $hit) => Base64::decode($hit))->mapInto(Line::class)->tap(function (Collection $lines) {
            $lines->each->shouldBePassedToCallback(false);
        });

        return collect([
            Line::create($line), ...$hits
        ]);
    }
}
