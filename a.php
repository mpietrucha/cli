<?php

require 'vendor/autoload.php';

buffer(function () {
    $handler = $this->handlers()->get(\Mpietrucha\Cli\Buffer\Handlers\SymfonyVarDumperHandler::class);

    $handler->encryptable();
    $handler->supportsColors(true);
});
