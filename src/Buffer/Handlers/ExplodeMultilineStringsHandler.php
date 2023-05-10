<?php

namespace Mpietrucha\Cli\Buffer\Handlers;

use Closure;
use Mpietrucha\Cli\Buffer\Entry;

class ExplodeMultilineStringsHandler extends AbstractHandler
{
    public function handle(Entry $entry, Closure $next): Entry
    {
        $entry = Entry::fromCollection(
            $entry->lines()->map->get()->map->toNewLineCollection()->flatten()
        );

        return $next($entry);
    }
}
